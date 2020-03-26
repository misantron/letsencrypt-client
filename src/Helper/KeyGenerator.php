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

use LetsEncrypt\Enum\ECKeyAlgorithm;
use LetsEncrypt\Enum\RSAKeyLength;
use LetsEncrypt\Exception\KeyGeneratorException;
use LetsEncrypt\Exception\KeyPairException;

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

    /**
     * @throws KeyGeneratorException
     * @throws KeyPairException
     */
    public function csr(string $commonName, array $subjects, string $privateKeyPath): string
    {
        $domains = [];
        foreach (array_values($subjects) as $index => $subject) {
            $domains[] = 'DNS.' . ($index + 1) . ' = ' . $subject;
        }

        $csrConfigTemplate = FileSystem::readFileContent(dirname(__DIR__, 2) . '/resources/csr.template');
        $csrConfigContent = sprintf($csrConfigTemplate, implode(PHP_EOL, $domains));
        $csrConfigFilePath = tempnam(sys_get_temp_dir(), 'lec_');

        try {
            FileSystem::writeFileContent($csrConfigFilePath, $csrConfigContent);

            $privateKey = openssl_pkey_get_private('file://' . $privateKeyPath);
            if ($privateKey === false) {
                throw KeyPairException::privateKeyInvalid();
            }

            $resource = openssl_csr_new(
                [
                    'commonName' => $commonName,
                ],
                $privateKey,
                [
                    'digest_alg' => 'sha256',
                    'config' => $csrConfigFilePath,
                ]
            );
            if ($resource === false) {
                throw KeyGeneratorException::csrCreateError();
            }

            if (openssl_csr_export($resource, $csr) === false) {
                throw KeyGeneratorException::csrExportError();
            }

            openssl_free_key($privateKey);

            return $csr;
        } finally {
            // delete temporary file anyway
            unlink($csrConfigFilePath);
        }
    }

    /**
     * Create OpenSSL key pair and store in file system.
     *
     * @throws KeyGeneratorException
     * @throws KeyPairException
     */
    private function key(array $config, string $privateKeyPath, string $publicKeyPath): void
    {
        $resource = openssl_pkey_new($config);
        if ($resource === false) {
            throw KeyGeneratorException::keyCreateError();
        }

        if (openssl_pkey_export($resource, $privateKey) === false) {
            throw KeyGeneratorException::keyExportError();
        }

        $details = openssl_pkey_get_details($resource);
        if ($details === false) {
            throw KeyPairException::privateKeyDetailsError();
        }

        FileSystem::writeFileContent($privateKeyPath, $privateKey);
        FileSystem::writeFileContent($publicKeyPath, $details['key']);

        openssl_pkey_free($resource);
    }
}
