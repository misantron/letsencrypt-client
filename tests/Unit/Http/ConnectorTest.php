<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

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
        $this->assertSame('https://example.com/acme/new-account', $connector->getNewAccountUrl());
        $this->assertSame('https://example.com/acme/new-order', $connector->getNewOrderUrl());
        $this->assertPropertySame('oFvnlFP1wIhRlYS2jTaXbA', 'nonce', $connector);
    }
}
