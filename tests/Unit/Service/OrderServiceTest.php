<?php

declare(strict_types=1);

namespace LetsEncrypt\Tests\Unit\Service;

use LetsEncrypt\Certificate\Bundle;
use LetsEncrypt\Certificate\Certificate;
use LetsEncrypt\Entity\Account;
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
            KEYS_PATH . DIRECTORY_SEPARATOR . Bundle::PRIVATE_KEY,
            KEYS_PATH . DIRECTORY_SEPARATOR . Bundle::PUBLIC_KEY
        );
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        // cleanup certificate directory
        if (is_dir(KEYS_PATH . DIRECTORY_SEPARATOR . 'example.org')) {
            unlink(KEYS_PATH . DIRECTORY_SEPARATOR . 'example.org');
        }
        // remove account key pair
        if (file_exists(KEYS_PATH . DIRECTORY_SEPARATOR . Bundle::PRIVATE_KEY)) {
            unlink(KEYS_PATH . DIRECTORY_SEPARATOR . Bundle::PRIVATE_KEY);
        }
        if (file_exists(KEYS_PATH . DIRECTORY_SEPARATOR . Bundle::PUBLIC_KEY)) {
            unlink(KEYS_PATH . DIRECTORY_SEPARATOR . Bundle::PUBLIC_KEY);
        }
    }

    public function testConstructor(): void
    {
        $service = new OrderService(new AuthorizationService(), KEYS_PATH);

        $this->assertPropertyInstanceOf(AuthorizationService::class, 'authorizationService', $service);
        $this->assertPropertySame(KEYS_PATH, 'filesPath', $service);
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
            KEYS_PATH . DIRECTORY_SEPARATOR . Bundle::PRIVATE_KEY
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
            ]
        );

        $service = $this->createService($connector);
        $order = $service->create($account, $domain, $subjects, $certificate);

        $this->assertDirectoryExists(KEYS_PATH . DIRECTORY_SEPARATOR . $domain);
        $this->assertFileExists($service->getOrderFilePath($domain));
        $this->assertFileExists($service->getPrivateKeyPath($domain));
        $this->assertFileExists($service->getPublicKeyPath($domain));

        $this->assertTrue($order->isPending());
        $this->assertSame('https://example.com/acme/order/TOlocE8rfgo', $order->getUrl());
        $this->assertSame($order->getUrl(), file_get_contents($service->getOrderFilePath($domain)));
        $this->assertSame('https://example.com/acme/order/TOlocE8rfgo/finalize', $order->getFinalizeUrl());
        $this->assertSame(['www.example.org', 'example.org'], $order->getIdentifiers());
    }

    /**
     * @depends testCreate
     */
    public function testGetCertificate(): void
    {
        $domain = 'example.org';

        $account = new Account(
            [],
            'https://example.com/acme/acct/evOfKhNU60wg',
            KEYS_PATH . DIRECTORY_SEPARATOR . Bundle::PRIVATE_KEY
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
                    ]
                ],
                'authorizations' => [],
                'finalize' => 'https://example.com/acme/order/TOlocE8rfgo/finalize',
            ],
            'https://example.com/acme/order/4E16bbL5iSw'
        );

        $connector = $this->createConnector();

        $this->appendResponseFixture('order.finalize.response.json', 200, [
            'Replay-Nonce' => 'CGf81JWBsq8QyIgPCi9Q9X',
            'Link' => '<https://example.com/acme/directory>;rel="index"',
            'Location' => 'https://example.com/acme/order/4E16bbL5iSw',
        ]);
        $this->appendResponseFixture('order.processing.response.json');
        $this->appendResponseFixture('order.processing.response.json');
        $this->appendResponseFixture('order.valid.response.json');
        $this->appendResponseFixture('certificate.response');

        $service = $this->createService($connector);
        $service->getCertificate($account, $order, $domain);

        $this->assertFileExists($service->getCertificatePath($domain));
        $this->assertFileExists($service->getFullChainCertificatePath($domain));
    }

    private function createService(Connector $connector): OrderService
    {
        $service = new OrderService(new AuthorizationService(), KEYS_PATH);
        $service->setConnector($connector);
        $service->setKeyGenerator(new KeyGenerator());

        return $service;
    }
}
