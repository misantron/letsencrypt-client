<?php

declare(strict_types=1);

namespace LetsEncrypt\Tests\Unit\Http;

use LetsEncrypt\Http\Connector;
use LetsEncrypt\Tests\ApiClientTestCase;

class ConnectorTest extends ApiClientTestCase
{
    public function testConstructor(): void
    {
        $this->appendResponseFixture('directory.response');

        $connector = new Connector(true, null, $this->getHttpClientMock());

        $this->assertSame('https://example.com/acme/new-nonce', $connector->getEndpoint()->newNonce);
        $this->assertSame('https://example.com/acme/new-account', $connector->getEndpoint()->newAccount);
        $this->assertSame('https://example.com/acme/new-order', $connector->getEndpoint()->newOrder);
    }
}
