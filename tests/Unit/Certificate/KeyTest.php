<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Tests\Unit\Certificate;

use LetsEncrypt\Certificate\Key;
use LetsEncrypt\Enum\ECKeyAlgorithm;
use LetsEncrypt\Enum\RSAKeyLength;
use LetsEncrypt\Tests\TestCase;

class KeyTest extends TestCase
{
    public function testRsaKey(): void
    {
        $key = Key::rsa(RSAKeyLength::bit4096());

        $this->assertTrue($key->isRSA());
        $this->assertFalse($key->isEC());
        $this->assertSame('4096', $key->getLength()->getValue());
        $this->assertNull($key->getAlgorithm());
    }

    public function testEcKey(): void
    {
        $key = Key::ec(ECKeyAlgorithm::prime256v1());

        $this->assertFalse($key->isRSA());
        $this->assertTrue($key->isEC());
        $this->assertSame('prime256v1', $key->getAlgorithm()->getValue());
        $this->assertNull($key->getLength());
    }
}
