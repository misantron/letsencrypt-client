<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Tests\Unit\Service;

use LetsEncrypt\Certificate\Bundle;
use LetsEncrypt\Certificate\Certificate;
use LetsEncrypt\Entity\Account;
use LetsEncrypt\Entity\Authorization;
use LetsEncrypt\Entity\Order;
use LetsEncrypt\Enum\RSAKeyLength;
use LetsEncrypt\Exception\EnvironmentException;
use LetsEncrypt\Helper\KeyGenerator;
use LetsEncrypt\Http\Connector;
use LetsEncrypt\Service\AuthorizationService;
use LetsEncrypt\Service\OrderService;
use LetsEncrypt\Tests\ApiClientTestCase;

class OrderServiceTest extends ApiClientTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // generate account key pair
        $keyGenerator = new KeyGenerator();
        $keyGenerator->rsa(
            static::getKeysPath() . Bundle::PRIVATE_KEY,
            static::getKeysPath() . Bundle::PUBLIC_KEY
        );
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        // cleanup certificate directory
        $certificateDirectoryPath = static::getKeysPath() . 'example.org' . DIRECTORY_SEPARATOR;
        $filesList = scandir($certificateDirectoryPath);
        if ($filesList !== false) {
            // remove all files from directory
            array_walk($filesList, static function (string $file) use ($certificateDirectoryPath) {
                if (is_file($certificateDirectoryPath . $file)) {
                    unlink($certificateDirectoryPath . $file);
                }
            });
            // finally remove empty directory
            rmdir($certificateDirectoryPath);
        }
        // remove account key pair
        if (file_exists(static::getKeysPath() . Bundle::PRIVATE_KEY)) {
            unlink(static::getKeysPath() . Bundle::PRIVATE_KEY);
        }
        if (file_exists(static::getKeysPath() . Bundle::PUBLIC_KEY)) {
            unlink(static::getKeysPath() . Bundle::PUBLIC_KEY);
        }
    }

    public function testConstructor(): void
    {
        $service = new OrderService(new AuthorizationService(), static::getKeysPath());

        $this->assertPropertyInstanceOf(AuthorizationService::class, 'authorizationService', $service);
        $this->assertPropertySame(
            rtrim(static::getKeysPath(), DIRECTORY_SEPARATOR),
            'filesPath',
            $service
        );
    }

    public function testConstructorWithInvalidFilesPath(): void
    {
        $this->expectException(EnvironmentException::class);
        $this->expectExceptionMessage('Certificates directory path "notExistDirectory" is not a directory');

        new OrderService(new AuthorizationService(), 'notExistDirectory');
    }

    public function testCreate(): void
    {
        $domain = 'example.org';
        $subjects = [
            'www.example.org',
            'example.org',
        ];
        $account = new Account(
            [],
            'https://example.com/acme/acct/evOfKhNU60wg',
            static::getKeysPath() . Bundle::PRIVATE_KEY
        );
        $certificate = Certificate::createWithRSAKey(RSAKeyLength::bit4096());

        $connector = $this->createConnector();

        $this->appendResponseFixture(
            'order.create.response.json',
            201,
            [
                'Replay-Nonce' => 'MYAuvOpaoIiywTezizk5vw',
                'Link' => '<https://example.com/acme/directory>;rel="index"',
                'Location' => 'https://example.com/acme/order/TOlocE8rfgo',
            ],
            [
                'identifiers' => [
                    [
                        'type' => 'dns',
                        'value' => 'www.example.org',
                    ],
                    [
                        'type' => 'dns',
                        'value' => 'example.org',
                    ],
                ],
                'notBefore' => '',
                'notAfter' => '',
            ]
        );

        $this->appendResponseFixture('authorization1.response.json');
        $this->appendResponseFixture('authorization2.response.json');

        $service = $this->createService($connector);
        $order = $service->create($account, $domain, $subjects, $certificate);

        $this->assertDirectoryExists(static::getKeysPath() . $domain);
        $this->assertFileExists($service->getOrderFilePath($domain));
        $this->assertFileExists($service->getPrivateKeyPath($domain));
        $this->assertFileExists($service->getPublicKeyPath($domain));

        $this->assertTrue($order->isPending());
        $this->assertSame('https://example.com/acme/order/TOlocE8rfgo', $order->getUrl());
        $this->assertSame($order->getUrl(), file_get_contents($service->getOrderFilePath($domain)));
        $this->assertSame('https://example.com/acme/order/TOlocE8rfgo/finalize', $order->getFinalizeUrl());
        $this->assertSame(['www.example.org', 'example.org'], $order->getIdentifiers());
        $this->assertCount(2, $order->getAuthorizations());
    }

    /**
     * @depends testCreate
     */
    public function testGetPendingAuthorizations(): void
    {
        $account = new Account(
            [],
            'https://example.com/acme/acct/evOfKhNU60wg',
            static::getKeysPath() . Bundle::PRIVATE_KEY
        );
        $order = new Order(
            [
                'identifiers' => [
                    [
                        'type' => 'dns',
                        'value' => 'www.example.org',
                    ],
                    [
                        'type' => 'dns',
                        'value' => 'example.org',
                    ],
                ],
                'authorizations' => [
                    new Authorization(
                        [
                            'status' => 'pending',
                            'identifier' => [
                                'type' => 'dns',
                                'value' => 'www.example.org',
                            ],
                            'challenges' => [
                                [
                                    'status' => 'pending',
                                    'type' => 'http-01',
                                    'url' => 'https://example.com/acme/chall/prV_B7yEyA4',
                                    'token' => 'DGyRejmCefe7v4NfDGDKfA',
                                ],
                                [
                                    'status' => 'pending',
                                    'type' => 'dns-01',
                                    'url' => 'https://example.com/acme/chall/Rg5dV14Gh1Q',
                                    'token' => 'DGyRejmCefe7v4NfDGDKfA',
                                ],
                            ],
                        ],
                        'https://example.com/acme/authz/PAniVnsZcis'
                    ),
                    new Authorization(
                        [
                            'status' => 'valid',
                            'identifier' => [
                                'type' => 'dns',
                                'value' => 'example.org',
                            ],
                            'challenges' => [],
                        ],
                        'https://example.com/acme/authz/r4HqLzrSrpI'
                    ),
                ],
            ],
            'https://example.com/acme/order/4E16bbL5iSw'
        );

        $connector = $this->createConnector();

        $service = $this->createService($connector);

        // test http authorizations
        $authorizations = $service->getPendingHttpAuthorizations($account, $order);

        $this->assertCount(1, $authorizations);
        $this->assertArrayHasKey('identifier', $authorizations[0]);
        $this->assertSame('www.example.org', $authorizations[0]['identifier']);
        $this->assertArrayHasKey('filename', $authorizations[0]);
        $this->assertSame('DGyRejmCefe7v4NfDGDKfA', $authorizations[0]['filename']);
        $this->assertArrayHasKey('content', $authorizations[0]);

        // test dns authorizations
        $authorizations = $service->getPendingDnsAuthorizations($account, $order);

        $this->assertCount(1, $authorizations);
        $this->assertArrayHasKey('identifier', $authorizations[0]);
        $this->assertSame('www.example.org', $authorizations[0]['identifier']);
        $this->assertArrayHasKey('dnsDigest', $authorizations[0]);
    }

    /**
     * @depends testCreate
     */
    public function testGet(): void
    {
        $domain = 'example.org';
        $subjects = [
            'www.example.org',
            'example.org',
        ];

        $connector = $this->createConnector();

        $this->appendResponseFixture('order.create.response.json');
        $this->appendResponseFixture('authorization1.response.json');
        $this->appendResponseFixture('authorization2.response.json');

        $service = $this->createService($connector);
        $order = $service->get($domain, $subjects);

        $this->assertTrue($order->isPending());
        $this->assertSame('https://example.com/acme/order/TOlocE8rfgo', $order->getUrl());
        $this->assertSame('https://example.com/acme/order/TOlocE8rfgo/finalize', $order->getFinalizeUrl());
        $this->assertSame(['www.example.org', 'example.org'], $order->getIdentifiers());
        $this->assertCount(2, $order->getAuthorizations());
    }

    /**
     * @depends testGet
     */
    public function testGetCertificate(): void
    {
        $domain = 'example.org';

        $account = new Account(
            [],
            'https://example.com/acme/acct/evOfKhNU60wg',
            static::getKeysPath() . Bundle::PRIVATE_KEY
        );
        $order = new Order(
            [
                'status' => 'ready',
                'identifiers' => [
                    [
                        'type' => 'dns',
                        'value' => 'www.example.org',
                    ],
                    [
                        'type' => 'dns',
                        'value' => 'example.org',
                    ],
                ],
                'authorizations' => [],
                'finalize' => 'https://example.com/acme/order/TOlocE8rfgo/finalize',
            ],
            'https://example.com/acme/order/4E16bbL5iSw'
        );

        $connector = $this->createConnector();

        // finalize order
        $this->appendResponseFixture('order.finalize.response.json', 200, [
            'Replay-Nonce' => 'CGf81JWBsq8QyIgPCi9Q9X',
            'Link' => '<https://example.com/acme/directory>;rel="index"',
            'Location' => 'https://example.com/acme/order/4E16bbL5iSw',
        ]);
        $this->appendResponseFixture('authorization1.response.json');
        $this->appendResponseFixture('authorization2.response.json');

        // 1st try: order still processing
        $this->appendResponseFixture('order.processing.response.json');
        $this->appendResponseFixture('authorization1.response.json');
        $this->appendResponseFixture('authorization2.response.json');

        // 2nd try: order still processing
        $this->appendResponseFixture('order.processing.response.json');
        $this->appendResponseFixture('authorization1.response.json');
        $this->appendResponseFixture('authorization2.response.json');

        // 3rd try: order is valid
        $this->appendResponseFixture('order.valid.response.json');
        $this->appendResponseFixture('authorization1.response.json');
        $this->appendResponseFixture('authorization2.response.json');

        // get certificate
        $this->appendResponseFixture('certificate.response');
        $this->appendResponseFixture(null, 200, ['Replay-Nonce' => 'IXVHDyxIRGcTE0VSblhPzw']);

        $service = $this->createService($connector);
        $service->getCertificate($account, $order, $domain);

        $this->assertFileExists($service->getCertificatePath($domain));
        $this->assertFileExists($service->getFullChainCertificatePath($domain));
    }

    private function createService(Connector $connector): OrderService
    {
        $authorizationService = new AuthorizationService();
        $authorizationService->setConnector($connector);

        $service = new OrderService($authorizationService, static::getKeysPath());
        $service->setConnector($connector);
        $service->setKeyGenerator(new KeyGenerator());

        return $service;
    }
}
