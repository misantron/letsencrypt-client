<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Helper;

interface SignerInterface
{
    public function jws(array $payload, string $url, string $nonce, string $privateKeyPath): array;

    public function kid(array $payload, string $kid, string $url, string $nonce, string $privateKeyPath): array;

    public function kty(string $privateKeyPath): string;

    public function jwk(string $privateKeyPath): array;
}
