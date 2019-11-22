<?php

declare(strict_types=1);

namespace LetsEncrypt\Tests\Unit\Http;

use LetsEncrypt\Entity\Endpoint;
use LetsEncrypt\Http\Connector;
use LetsEncrypt\Tests\ApiClientTestCase;

class ConnectorTest extends ApiClientTestCase
{
    public function testConstructor(): void
    {
        $this->appendResponseFixture('directory.response');

        $connector = new Connector(true, null, $this->getHttpClientMock());

        $this->assertPropertyInstanceOf(Endpoint::class, 'endpoint', $connector);
        $this->assertSame('https://example.com/acme/new-account', $connector->getNewAccountEndpoint());
        $this->assertSame('https://example.com/acme/new-order', $connector->getNewOrderEndpoint());
    }
}
