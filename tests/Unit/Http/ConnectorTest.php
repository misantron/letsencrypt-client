<?php

declare(strict_types=1);

namespace LetsEncrypt\Tests\Unit\Http;

use LetsEncrypt\Entity\Endpoint;
use LetsEncrypt\Helper\Signer;
use LetsEncrypt\Tests\ApiClientTestCase;

class ConnectorTest extends ApiClientTestCase
{
    public function testConstructor(): void
    {
        $connector = $this->createConnector();

        $this->assertPropertyInstanceOf(Endpoint::class, 'endpoint', $connector);
        $this->assertPropertyInstanceOf(Signer::class, 'signer', $connector);
        $this->assertSame('https://example.com/acme/new-account', $connector->getNewAccountEndpoint());
        $this->assertSame('https://example.com/acme/new-order', $connector->getNewOrderEndpoint());
        $this->assertPropertySame('oFvnlFP1wIhRlYS2jTaXbA', 'nonce', $connector);
    }
}
