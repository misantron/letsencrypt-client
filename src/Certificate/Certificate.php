<?php

declare(strict_types=1);

namespace LetsEncrypt\Certificate;

use LetsEncrypt\Enum\ECKeyAlgorithm;
use LetsEncrypt\Enum\RSAKeyLength;

final class Certificate
{
    /**
     * @var Key
     */
    private $key;

    /**
     * @var \DateTimeImmutable
     */
    private $notBefore;

    /**
     * @var \DateTimeImmutable
     */
    private $notAfter;

    private function __construct(string $notBefore, string $notAfter, Key $key)
    {
        $this->notBefore = new \DateTimeImmutable($notBefore);
        $this->notAfter = new \DateTimeImmutable($notAfter);
        $this->key = $key;
    }

    public static function createWithRSAKey(string $notBefore, string $notAfter, RSAKeyLength $keyLength): self
    {
        return new static($notBefore, $notAfter, Key::rsa($keyLength));
    }

    public static function createWithECKey(string $notBefore, string $notAfter, ECKeyAlgorithm $algorithm): self
    {
        return new static($notBefore, $notAfter, Key::ec($algorithm));
    }

    public function getNotBefore(): string
    {
        return $this->notBefore->format(DATE_RFC3339);
    }

    public function getNotAfter(): string
    {
        return $this->notAfter->format(DATE_RFC3339);
    }

    public function getKey(): Key
    {
        return $this->key;
    }
}
