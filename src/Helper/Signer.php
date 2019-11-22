<?php

declare(strict_types=1);

namespace LetsEncrypt\Helper;

final class Signer
{
    public static function jwk(array $payload, string $url, string $nonce, string $privateKeyPath): array
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
                'n' => Base64::urlSafeEncode($details['rsa']['n']),
                'e' => Base64::urlSafeEncode($details['rsa']['e']),
            ],
            'nonce' => $nonce,
            'url' => $url,
        ];

        return self::sign($protected, $payload, $privateKey);
    }

    public static function kid(array $payload, string $kid, string $url, string $nonce, string $privateKeyPath): array
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

        return self::sign($protected, $payload, $privateKey);
    }

    private static function sign(array $protected, array $payload, $privateKey): array
    {
        $payloadEncoded = Base64::urlSafeEncode(str_replace('\\/', '/', json_encode($payload)));
        $protectedEncoded = Base64::urlSafeEncode(json_encode($protected));

        openssl_sign($protectedEncoded . '.' . $payloadEncoded, $signature, $privateKey, 'SHA256');

        return [
            'protected' => $protectedEncoded,
            'payload' => $payloadEncoded,
            'signature' => Base64::urlSafeEncode($signature),
        ];
    }
}
