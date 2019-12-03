<?php

declare(strict_types=1);

namespace LetsEncrypt\Tests\Unit\Service;

use LetsEncrypt\Certificate\Bundle;
use LetsEncrypt\Exception\EnvironmentException;
use LetsEncrypt\Helper\KeyGenerator;
use LetsEncrypt\Http\Connector;
use LetsEncrypt\Service\AccountService;
use LetsEncrypt\Tests\ApiClientTestCase;

class AccountServiceTest extends ApiClientTestCase
{
    public function testConstructor(): void
    {
        $service = new AccountService(static::getKeysPath());

        $this->assertPropertySame(
            rtrim(static::getKeysPath(), DIRECTORY_SEPARATOR),
            'keysPath',
            $service
        );
    }

    public function testConstructorWithInvalidKeysPath(): void
    {
        $this->expectException(EnvironmentException::class);
        $this->expectExceptionMessage('Account keys directory path "notExistDirectory" is not a directory');

        new AccountService('notExistDirectory');
    }

    public function testGetBeforeCreate(): void
    {
        $privateKeyPath = static::getKeysPath() . Bundle::PRIVATE_KEY;

        $this->expectException(EnvironmentException::class);
        $this->expectExceptionMessage('Private key "' . $privateKeyPath . '" does not exist');

        $connector = $this->createConnector();

        $service = $this->createService($connector);
        $service->get();
    }

    public function testCreate(): void
    {
        $emails = [
            'cert-admin@example.org',
            'admin@example.org',
        ];

        $connector = $this->createConnector();

        $this->appendResponseFixture(
            'account.create.response.json',
            201,
            [
                'Content-Type' => 'application/json',
                'Replay-Nonce' => 'D8s4D2mLs8Vn-goWuPQeKA',
                'Link' => '<https://example.com/acme/directory>;rel="index"',
                'Location' => 'https://example.com/acme/acct/evOfKhNU60wg',
            ],
            [
                'contact' => [
                    'mailto:cert-admin@example.org',
                    'mailto:admin@example.org',
                ],
                'termsOfServiceAgreed' => true,
            ]
        );

        $account = $this->createService($connector)->create($emails);

        $this->assertSame('https://example.com/acme/acct/evOfKhNU60wg', $account->getUrl());
        $this->assertSame([
            'mailto:cert-admin@example.org',
            'mailto:admin@example.org',
        ], $account->contact);
        $this->assertSame('46.231.212.68', $account->initialIp);
        $this->assertTrue($account->isValid());

        $this->assertCount(3, $this->getRequestHistory());
    }

    /**
     * @depends testCreate
     */
    public function testGet(): void
    {
        $connector = $this->createConnector();

        $this->appendResponseFixture(
            'account.get.response.json',
            200,
            [
                'Content-Type' => 'application/json',
                'Replay-Nonce' => 'D8s4D2mLs8Vn-goWuPQeKA',
                'Link' => '<https://example.com/acme/directory>;rel="index"',
                'Location' => 'https://example.com/acme/acct/evOfKhNU60wg',
            ],
            [
                'onlyReturnExisting' => true,
            ]
        );

        $account = $this->createService($connector)->get();

        $this->assertSame('https://example.com/acme/acct/evOfKhNU60wg', $account->getUrl());
        $this->assertSame([
            'mailto:cert-admin@example.org',
            'mailto:admin@example.org',
        ], $account->contact);
        $this->assertSame('46.231.212.68', $account->initialIp);
        $this->assertTrue($account->isValid());

        $this->assertCount(3, $this->getRequestHistory());
    }

    /**
     * @depends testGet
     */
    public function testUpdate(): void
    {
        $emails = [
            'admin@example.org',
        ];

        $connector = $this->createConnector();

        $this->appendResponseFixture(
            'account.get.response.json',
            200,
            [
                'Content-Type' => 'application/json',
                'Replay-Nonce' => 'D8s4D2mLs8Vn-goWuPQeKA',
                'Link' => '<https://example.com/acme/directory>;rel="index"',
                'Location' => 'https://example.com/acme/acct/evOfKhNU60wg',
            ],
            [
                'onlyReturnExisting' => true,
            ]
        );

        $this->appendResponseFixture(
            'account.update.response.json',
            200,
            [
                'Content-Type' => 'application/json',
                'Replay-Nonce' => 'D8s4D2mLs8Vn-goWuPQeKA',
                'Link' => '<https://example.com/acme/directory>;rel="index"',
                'Location' => 'https://example.com/acme/acct/evOfKhNU60wg',
            ],
            [
                'contact' => [
                    'mailto:admin@example.org',
                ],
            ]
        );

        $account = $this->createService($connector)->update($emails);

        $this->assertSame('https://example.com/acme/acct/evOfKhNU60wg', $account->getUrl());
        $this->assertSame([
            'mailto:admin@example.org',
        ], $account->contact);
        $this->assertSame('46.231.212.68', $account->initialIp);
        $this->assertTrue($account->isValid());

        $this->assertCount(4, $this->getRequestHistory());
    }

    /**
     * @depends testUpdate
     */
    public function testDeactivate(): void
    {
        $connector = $this->createConnector();

        $this->appendResponseFixture(
            'account.get.response.json',
            200,
            [
                'Content-Type' => 'application/json',
                'Replay-Nonce' => 'D8s4D2mLs8Vn-goWuPQeKA',
                'Link' => '<https://example.com/acme/directory>;rel="index"',
                'Location' => 'https://example.com/acme/acct/evOfKhNU60wg',
            ],
            [
                'onlyReturnExisting' => true,
            ]
        );

        $this->appendResponseFixture(
            'account.deactivate.response.json',
            200,
            [
                'Content-Type' => 'application/json',
                'Replay-Nonce' => 'D8s4D2mLs8Vn-goWuPQeKA',
                'Link' => '<https://example.com/acme/directory>;rel="index"',
                'Location' => 'https://example.com/acme/acct/evOfKhNU60wg',
            ],
            [
                'status' => 'deactivated',
            ]
        );

        $account = $this->createService($connector)->deactivate();

        $this->assertSame('https://example.com/acme/acct/evOfKhNU60wg', $account->getUrl());
        $this->assertSame([
            'mailto:admin@example.org',
        ], $account->contact);
        $this->assertSame('46.231.212.68', $account->initialIp);
        $this->assertTrue($account->isDeactivated());

        $this->assertCount(4, $this->getRequestHistory());
    }

    private function createService(Connector $connector): AccountService
    {
        $service = new AccountService(static::getKeysPath());
        $service->setKeyGenerator(new KeyGenerator());
        $service->setConnector($connector);

        return $service;
    }
}
