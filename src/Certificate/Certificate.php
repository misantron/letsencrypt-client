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
     * @var \DateTimeImmutable|null
     */
    private $notBefore;

    /**
     * @var \DateTimeImmutable|null
     */
    private $notAfter;

    private function __construct(Key $key, ?string $notBefore, ?string $notAfter)
    {
        $this->key = $key;
        $this->notBefore = $notBefore !== null ? new \DateTimeImmutable($notBefore) : null;
        $this->notAfter = $notAfter !== null ? new \DateTimeImmutable($notAfter) : null;
    }

    public static function createWithRSAKey(RSAKeyLength $keyLength, string $notBefore = null, string $notAfter = null): self
    {
        return new static(Key::rsa($keyLength), $notBefore, $notAfter);
    }

    public static function createWithECKey(ECKeyAlgorithm $algorithm, string $notBefore = null, string $notAfter = null): self
    {
        return new static(Key::ec($algorithm), $notBefore, $notAfter);
    }

    public function getNotBefore(): string
    {
        return $this->notBefore !== null ? $this->notBefore->format(DATE_RFC3339) : '';
    }

    public function getNotAfter(): string
    {
        return $this->notAfter !== null ? $this->notAfter->format(DATE_RFC3339) : '';
    }

    public function getKey(): Key
    {
        return $this->key;
    }
}
