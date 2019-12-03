<?php

declare(strict_types=1);

namespace LetsEncrypt\Helper;

interface SignerInterface
{
    public function jws(array $payload, string $url, string $nonce, string $privateKeyPath): array;

    public function kid(array $payload, string $kid, string $url, string $nonce, string $privateKeyPath): array;

    public function kty(string $privateKeyPath): string;
}
