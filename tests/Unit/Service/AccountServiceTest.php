<?php

declare(strict_types=1);

namespace LetsEncrypt\Tests\Unit\Service;

use LetsEncrypt\Helper\KeyGenerator;
use LetsEncrypt\Service\AccountService;
use LetsEncrypt\Tests\ApiClientTestCase;

class AccountServiceTest extends ApiClientTestCase
{
    public function testCreate(): void
    {
        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL PHP extension required');
        }

        $emails = [
            'cert-admin@example.org',
            'admin@example.org',
        ];

        $connector = $this->createConnector();

        $this->appendResponseFixture('account.create.response', 201, [
            'Content-Type' => 'application/json',
            'Replay-Nonce' => 'D8s4D2mLs8Vn-goWuPQeKA',
            'Link' => '<https://example.com/acme/directory>;rel="index"',
            'Location' => 'https://example.com/acme/acct/evOfKhNU60wg',
        ]);
        $this->appendResponseFixture('account.profile.response', 200);
        $this->appendResponseFixture(null, 200, ['Replay-Nonce' => 'oFvnlFP1wIhRlYS2jTaXbA']);

        $service = new AccountService(KEYS_PATH);
        $service->setKeyGenerator(new KeyGenerator());
        $service->setConnector($connector);

        $account = $service->create($emails);

        $this->assertSame('https://example.com/acme/acct/evOfKhNU60wg', $account->getUrl());
        $this->assertSame([
            'mailto:cert-admin@example.org',
            'mailto:admin@example.org'
        ], $account->contact);
        $this->assertTrue($account->isValid());
    }
}
