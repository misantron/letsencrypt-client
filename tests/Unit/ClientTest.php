<?php

declare(strict_types=1);

namespace LetsEncrypt\Tests\Unit;

use LetsEncrypt\Client;
use LetsEncrypt\Http\Connector;
use LetsEncrypt\Logger\Logger;
use LetsEncrypt\Logger\LogStrategy;
use LetsEncrypt\Service\AccountService;
use LetsEncrypt\Service\OrderService;
use LetsEncrypt\Tests\TestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Psr\Log\LoggerInterface;

class ClientTest extends TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = vfsStream::setup('root', null, ['_account' => []]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->root = null;
    }

    public function testConstructorWithDefaults(): void
    {
        $client = new Client($this->root->getChild('_account')->url(), $this->root->url());

        $this->assertPropertyInstanceOf(Connector::class, 'connector', $client);
        $this->assertPropertyInstanceOf(AccountService::class, 'accountService', $client);
        $this->assertPropertyInstanceOf(OrderService::class, 'orderService', $client);
    }

    public function testConstructorWithLogger(): void
    {
        $logger = new Logger(
            $this->createMock(LoggerInterface::class),
            LogStrategy::requestsOnly()
        );
        $client = new Client(
            $this->root->getChild('_account')->url(),
            $this->root->url(),
            true,
            $logger
        );

        $this->assertPropertyInstanceOf(Connector::class, 'connector', $client);
        $this->assertPropertyInstanceOf(AccountService::class, 'accountService', $client);
        $this->assertPropertyInstanceOf(OrderService::class, 'orderService', $client);
    }
}
