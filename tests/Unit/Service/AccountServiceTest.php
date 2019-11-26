<?php

declare(strict_types=1);

namespace LetsEncrypt\Tests\Unit\Service;

use LetsEncrypt\Helper\KeyGenerator;
use LetsEncrypt\Http\Connector;
use LetsEncrypt\Service\AccountService;
use LetsEncrypt\Tests\ApiClientTestCase;

class AccountServiceTest extends ApiClientTestCase
{
    public function testCreate(): void
    {
        $emails = [
            'cert-admin@example.org',
            'admin@example.org',
        ];

        $connector = $this->createConnector();

        $this->appendResponseFixture(
            'account.create.response',
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
                    'mailto:admin@example.org'
                ],
                'termsOfServiceAgreed' => true,
            ]
        );

        $account = $this->createService($connector)->create($emails);

        $this->assertSame('https://example.com/acme/acct/evOfKhNU60wg', $account->getUrl());
        $this->assertSame([
            'mailto:cert-admin@example.org',
            'mailto:admin@example.org'
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
            'account.get.response',
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
            'mailto:admin@example.org'
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
            'account.get.response',
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
            'account.update.response',
            200,
            [
                'Content-Type' => 'application/json',
                'Replay-Nonce' => 'D8s4D2mLs8Vn-goWuPQeKA',
                'Link' => '<https://example.com/acme/directory>;rel="index"',
                'Location' => 'https://example.com/acme/acct/evOfKhNU60wg',
            ],
            [
                'contact' => [
                    'mailto:admin@example.org'
                ],
            ]
        );

        $account = $this->createService($connector)->update($emails);

        $this->assertSame('https://example.com/acme/acct/evOfKhNU60wg', $account->getUrl());
        $this->assertSame([
            'mailto:admin@example.org'
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
            'account.get.response',
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
            'account.deactivate.response',
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
            'mailto:admin@example.org'
        ], $account->contact);
        $this->assertSame('46.231.212.68', $account->initialIp);
        $this->assertTrue($account->isDeactivated());

        $this->assertCount(4, $this->getRequestHistory());
    }

    private function createService(Connector $connector): AccountService
    {
        $service = new AccountService(KEYS_PATH);
        $service->setKeyGenerator(new KeyGenerator());
        $service->setConnector($connector);

        return $service;
    }
}
