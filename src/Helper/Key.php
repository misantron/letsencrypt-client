<?php

declare(strict_types=1);

namespace LetsEncrypt\Helper;

use LetsEncrypt\Enum\ECKeyAlgorithm;
use LetsEncrypt\Enum\RSAKeyLength;

final class Key
{
    public static function rsa(string $privateKeyPath, string $publicKeyPath, RSAKeyLength $length = null): void
    {
        if ($length === null) {
            $length = RSAKeyLength::bit2048();
        }

        self::key(
            [
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'private_key_bits' => (int) $length->getValue(),
            ],
            $privateKeyPath,
            $publicKeyPath
        );
    }

    public static function ec(string $privateKeyPath, string $publicKeyPath, ECKeyAlgorithm $type = null): void
    {
        if ($type === null) {
            $type = ECKeyAlgorithm::prime256v1();
        }

        self::key(
            [
                'private_key_type' => OPENSSL_KEYTYPE_EC,
                'curve_name' => $type->getValue(),
            ],
            $privateKeyPath,
            $publicKeyPath
        );
    }

    private static function key(array $config, string $privateKeyPath, string $publicKeyPath): void
    {
        $res = openssl_pkey_new($config);
        if ($res === false) {
            $error = "Could not generate key pair! Check your OpenSSL configuration. OpenSSL Error: " . PHP_EOL;
            while ($message = openssl_error_string()) {
                $error .= $message . PHP_EOL;
            }
            throw new \RuntimeException($error);
        }

        if (openssl_pkey_export($res, $privateKey) === false) {
            $error = "RSA keypair export failed!! Error: " . PHP_EOL;
            while ($message = openssl_error_string()) {
                $error .= $message . PHP_EOL;
            }
            throw new \RuntimeException($error);
        }

        $details = openssl_pkey_get_details($res);
        if ($details === false) {

        }

        file_put_contents($privateKeyPath, $privateKey);
        file_put_contents($publicKeyPath, $details['key']);

        openssl_pkey_free($res);
    }
}
