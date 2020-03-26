<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019-2020
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Tests\Unit\Http;

use LetsEncrypt\Http\GooglePublicDNS;
use LetsEncrypt\Tests\ApiClientTestCase;

class GooglePublicDNSTest extends ApiClientTestCase
{
    public function testVerifySuccess(): void
    {
        $connector = $this->createConnector();

        $this->appendResponseFixture('google.dns.success.response.json');

        $service = new GooglePublicDNS();
        $service->setConnector($connector);

        $this->assertTrue($service->verify('test.com', 'Z0lrCsDEMPH9E1STFX99h_mTh3ae3jekB8etxVbFzjA'));
    }

    public function testVerifyFailed(): void
    {
        $connector = $this->createConnector();

        $this->appendResponseFixture('google.dns.failed.response.json');

        $service = new GooglePublicDNS();
        $service->setConnector($connector);

        $this->assertFalse($service->verify('vk.com', 'Z0lrCsDEMPH9E1STFX99h_mTh3ae3jekB8etxVbFzjA'));
    }
}
