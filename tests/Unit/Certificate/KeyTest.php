<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019-2020
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Tests\Unit\Certificate;

use LetsEncrypt\Certificate\Key;
use LetsEncrypt\Enum\ECKeyAlgorithm;
use LetsEncrypt\Enum\RSAKeyLength;
use LetsEncrypt\Tests\TestCase;

class KeyTest extends TestCase
{
    public function testRsa(): void
    {
        $key = Key::rsa(RSAKeyLength::bit4096());

        $this->assertSame('rsa', $key->getType()->getValue());
        $this->assertPropertyInstanceOf(RSAKeyLength::class, 'length', $key);
        $this->assertPropertyNull('algorithm', $key);
    }

    public function testEc(): void
    {
        $key = Key::ec(ECKeyAlgorithm::prime256v1());

        $this->assertSame('ec', $key->getType()->getValue());
        $this->assertPropertyInstanceOf(ECKeyAlgorithm::class, 'algorithm', $key);
        $this->assertPropertyNull('length', $key);
    }
}
