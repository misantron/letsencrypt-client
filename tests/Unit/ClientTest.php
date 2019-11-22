<?php

declare(strict_types=1);

namespace LetsEncrypt\Tests\Unit;

use LetsEncrypt\Client;
use LetsEncrypt\Service\AccountService;
use LetsEncrypt\Service\OrderService;
use LetsEncrypt\Tests\TestCase;
use org\bovigo\vfs\vfsStream;

class ClientTest extends TestCase
{
    public function testConstructor(): void
    {
        $root = vfsStream::setup('root', null, ['_account' => []]);

        $client = new Client($root->getChild('_account')->url(), $root->url());

        $this->assertPropertyInstanceOf(AccountService::class, 'accountService', $client);
        $this->assertPropertyInstanceOf(OrderService::class, 'orderService', $client);
    }
}
