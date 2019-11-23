<?php

declare(strict_types=1);

namespace LetsEncrypt\Tests\Unit\Certificate;

use LetsEncrypt\Certificate\Certificate;
use LetsEncrypt\Enum\ECKeyAlgorithm;
use LetsEncrypt\Enum\RSAKeyLength;
use LetsEncrypt\Tests\TestCase;

class CertificateTest extends TestCase
{
    public function testCreateWithRsaKey(): void
    {
        $notBefore = '2016-01-01 00:00:00';
        $notAfter = '2016-01-08 00:00:00';

        $certificate = Certificate::createWithRSAKey($notBefore, $notAfter, RSAKeyLength::bit2048());

        $this->assertSame('2016-01-01T00:00:00+00:00', $certificate->getNotBefore());
        $this->assertSame('2016-01-08T00:00:00+00:00', $certificate->getNotAfter());
        $this->assertInstanceOf(RSAKeyLength::class, $certificate->getKey()->getLength());
    }

    public function testCreateWithEcKey(): void
    {
        $notBefore = '2016-01-01 00:00:00';
        $notAfter = '2016-01-08 00:00:00';

        $certificate = Certificate::createWithECKey($notBefore, $notAfter, ECKeyAlgorithm::secp384r1());

        $this->assertSame('2016-01-01T00:00:00+00:00', $certificate->getNotBefore());
        $this->assertSame('2016-01-08T00:00:00+00:00', $certificate->getNotAfter());
        $this->assertInstanceOf(ECKeyAlgorithm::class, $certificate->getKey()->getAlgorithm());
    }
}