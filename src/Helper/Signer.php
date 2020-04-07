<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019-2020
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Helper;

use LetsEncrypt\Exception\KeyPairException;

class Signer implements SignerInterface
{
    /**
     * @var Base64SafeEncoder
     */
    private $base64Encoder;

    public function __construct(Base64SafeEncoder $base64Encoder)
    {
        $this->base64Encoder = $base64Encoder;
    }

    public static function createWithBase64SafeEncoder(): self
    {
        return new static(new Base64SafeEncoder());
    }

    public function getBase64Encoder(): Base64SafeEncoder
    {
        return $this->base64Encoder;
    }

    /**
     * @throws KeyPairException
     */
    public function jws(array $payload, string $url, string $nonce, string $privateKeyPath): array
    {
        $privateKey = openssl_pkey_get_private('file://' . $privateKeyPath);
        if ($privateKey === false) {
            throw KeyPairException::privateKeyInvalid();
        }

        $protected = [
            'alg' => 'RS256',
            'jwk' => $this->jwk($privateKeyPath),
            'nonce' => $nonce,
            'url' => $url,
        ];

        return $this->sign($protected, $payload, $privateKey);
    }

    /**
     * @throws KeyPairException
     */
    public function kid(array $payload, string $kid, string $url, string $nonce, string $privateKeyPath): array
    {
        $privateKey = openssl_pkey_get_private('file://' . $privateKeyPath);
        if ($privateKey === false) {
            throw KeyPairException::privateKeyInvalid();
        }

        $protected = [
            'alg' => 'RS256',
            'kid' => $kid,
            'nonce' => $nonce,
            'url' => $url,
        ];

        return $this->sign($protected, $payload, $privateKey);
    }

    /**
     * @throws KeyPairException
     */
    public function kty(string $privateKeyPath): string
    {
        $header = json_encode($this->jwk($privateKeyPath));

        return $this->base64Encoder->hashEncode($header);
    }

    /**
     * @throws KeyPairException
     */
    public function jwk(string $privateKeyPath): array
    {
        $privateKey = openssl_pkey_get_private('file://' . $privateKeyPath);
        if ($privateKey === false) {
            throw KeyPairException::privateKeyInvalid();
        }

        $details = openssl_pkey_get_details($privateKey);
        if ($details === false) {
            throw KeyPairException::privateKeyDetailsError();
        }

        return [
            'kty' => 'RSA',
            'n' => $this->base64Encoder->encode($details['rsa']['n']),
            'e' => $this->base64Encoder->encode($details['rsa']['e']),
        ];
    }

    private function sign(array $protected, array $payload, $privateKey): array
    {
        // empty payload array must be encoded to empty string
        $payloadEncoded = $payload !== [] ?
            $this->base64Encoder->encode(str_replace('\\/', '/', json_encode($payload))) :
            '';
        $protectedEncoded = $this->base64Encoder->encode(json_encode($protected));

        openssl_sign($protectedEncoded . '.' . $payloadEncoded, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return [
            'protected' => $protectedEncoded,
            'payload' => $payloadEncoded,
            'signature' => $this->base64Encoder->encode($signature),
        ];
    }
}
