<?php

declare(strict_types=1);

namespace LetsEncrypt\Tests\Unit\Certificate;

use LetsEncrypt\Certificate\Certificate;
use LetsEncrypt\Certificate\Key;
use LetsEncrypt\Enum\ECKeyAlgorithm;
use LetsEncrypt\Enum\RSAKeyLength;
use LetsEncrypt\Tests\TestCase;

class CertificateTest extends TestCase
{
    public function testCreateWithRsaKey(): void
    {
        $certificate = Certificate::createWithRSAKey(RSAKeyLength::bit2048());

        $this->assertPropertyInstanceOf(Key::class, 'key', $certificate);
        $this->assertInstanceOf(RSAKeyLength::class, $certificate->getKey()->getLength());
        $this->assertSame('', $certificate->getNotBefore());
        $this->assertSame('', $certificate->getNotAfter());

        $notBefore = '2016-01-01 00:00:00';
        $notAfter = '2016-01-08 00:00:00';

        $certificate = Certificate::createWithRSAKey(RSAKeyLength::bit2048(), $notBefore, $notAfter);

        $this->assertPropertyInstanceOf(Key::class, 'key', $certificate);
        $this->assertInstanceOf(RSAKeyLength::class, $certificate->getKey()->getLength());
        $this->assertSame('2016-01-01T00:00:00+00:00', $certificate->getNotBefore());
        $this->assertSame('2016-01-08T00:00:00+00:00', $certificate->getNotAfter());
    }

    public function testCreateWithEcKey(): void
    {
        $certificate = Certificate::createWithECKey(ECKeyAlgorithm::secp384r1());

        $this->assertPropertyInstanceOf(Key::class, 'key', $certificate);
        $this->assertInstanceOf(ECKeyAlgorithm::class, $certificate->getKey()->getAlgorithm());
        $this->assertSame('', $certificate->getNotBefore());
        $this->assertSame('', $certificate->getNotAfter());

        $notBefore = '2016-01-01 00:00:00';
        $notAfter = '2016-01-08 00:00:00';

        $certificate = Certificate::createWithECKey(ECKeyAlgorithm::secp384r1(), $notBefore, $notAfter);

        $this->assertPropertyInstanceOf(Key::class, 'key', $certificate);
        $this->assertInstanceOf(ECKeyAlgorithm::class, $certificate->getKey()->getAlgorithm());
        $this->assertSame('2016-01-01T00:00:00+00:00', $certificate->getNotBefore());
        $this->assertSame('2016-01-08T00:00:00+00:00', $certificate->getNotAfter());
    }
}
