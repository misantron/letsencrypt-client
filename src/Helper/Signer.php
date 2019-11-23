<?php

declare(strict_types=1);

namespace LetsEncrypt\Helper;

final class Signer implements SignerInterface
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

    public function jwk(array $payload, string $url, string $nonce, string $privateKeyPath): array
    {
        $privateKey = openssl_pkey_get_private('file://' . $privateKeyPath);
        if ($privateKey === false) {

        }

        $details = openssl_pkey_get_details($privateKey);
        if ($details === false) {

        }

        $protected = [
            'alg' => 'RS256',
            'jwk' => [
                'kty' => 'RSA',
                'n' => $this->base64Encoder->encode($details['rsa']['n']),
                'e' => $this->base64Encoder->encode($details['rsa']['e']),
            ],
            'nonce' => $nonce,
            'url' => $url,
        ];

        return $this->sign($protected, $payload, $privateKey);
    }

    public function kid(array $payload, string $kid, string $url, string $nonce, string $privateKeyPath): array
    {
        $privateKey = openssl_pkey_get_private('file://' . $privateKeyPath);
        if ($privateKey === false) {

        }

        $protected = [
            'alg' => 'RS256',
            'kid' => $kid,
            'nonce' => $nonce,
            'url' => $url,
        ];

        return $this->sign($protected, $payload, $privateKey);
    }

    public function kty(string $privateKeyPath): string
    {
        $privateKey = openssl_pkey_get_private('file://' . $privateKeyPath);
        if ($privateKey === false) {

        }
        $details = openssl_pkey_get_details($privateKey);
        if ($details === false) {

        }

        $header = [
            'kty' => 'RSA',
            'n' => $this->base64Encoder->encode($details['rsa']['n']),
            'e' => $this->base64Encoder->encode($details['rsa']['e']),
        ];

        return $this->base64Encoder->hashEncode(json_encode($header));
    }

    private function sign(array $protected, array $payload, $privateKey): array
    {
        $payloadEncoded = $this->base64Encoder->encode(str_replace('\\/', '/', json_encode($payload)));
        $protectedEncoded = $this->base64Encoder->encode(json_encode($protected));

        openssl_sign($protectedEncoded . '.' . $payloadEncoded, $signature, $privateKey, 'SHA256');

        return [
            'protected' => $protectedEncoded,
            'payload' => $payloadEncoded,
            'signature' => $this->base64Encoder->encode($signature),
        ];
    }
}
