<?php

declare(strict_types=1);

namespace LetsEncrypt\Tests\Unit\Http;

use LetsEncrypt\Entity\Endpoint;
use LetsEncrypt\Helper\Signer;
use LetsEncrypt\Http\Connector;
use LetsEncrypt\Tests\ApiClientTestCase;

class ConnectorTest extends ApiClientTestCase
{
    public function testConstructor(): void
    {
        $this->appendResponseFixture('directory.response');
        $this->appendResponseFixture(null, 200, ['Replay-Nonce' => 'oFvnlFP1wIhRlYS2jTaXbA']);

        $connector = new Connector(true, null, $this->getHttpClientMock());

        $this->assertPropertyInstanceOf(Endpoint::class, 'endpoint', $connector);
        $this->assertPropertyInstanceOf(Signer::class, 'signer', $connector);
        $this->assertSame('https://example.com/acme/new-account', $connector->getNewAccountEndpoint());
        $this->assertSame('https://example.com/acme/new-order', $connector->getNewOrderEndpoint());
        $this->assertPropertySame('oFvnlFP1wIhRlYS2jTaXbA', 'nonce', $connector);
    }
}
