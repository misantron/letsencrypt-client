<?php

declare(strict_types=1);

namespace LetsEncrypt\Helper;

use LetsEncrypt\Enum\ECKeyAlgorithm;
use LetsEncrypt\Enum\RSAKeyLength;

final class KeyGenerator
{
    public function rsa(string $privateKeyPath, string $publicKeyPath, RSAKeyLength $length = null): void
    {
        if ($length === null) {
            $length = RSAKeyLength::bit2048();
        }

        $this->key(
            [
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'private_key_bits' => (int) $length->getValue(),
            ],
            $privateKeyPath,
            $publicKeyPath
        );
    }

    public function ec(string $privateKeyPath, string $publicKeyPath, ECKeyAlgorithm $type = null): void
    {
        if ($type === null) {
            $type = ECKeyAlgorithm::prime256v1();
        }

        $this->key(
            [
                'private_key_type' => OPENSSL_KEYTYPE_EC,
                'curve_name' => $type->getValue(),
            ],
            $privateKeyPath,
            $publicKeyPath
        );
    }

    private function key(array $config, string $privateKeyPath, string $publicKeyPath): void
    {
        $res = openssl_pkey_new($config);
        if ($res === false) {
            $error = 'Unable to generate key pair:' . PHP_EOL;
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

        FileSystem::writeFileContent($privateKeyPath, $privateKey);
        FileSystem::writeFileContent($publicKeyPath, $details['key']);

        openssl_pkey_free($res);
    }
}
