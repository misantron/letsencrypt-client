<?php

declare(strict_types=1);

namespace LetsEncrypt\Exception;

final class KeyGeneratorException extends OpenSSLException
{
    private function __construct(string $prefix)
    {
        // collect OpenSSL error messages
        $error = $prefix . ':' . PHP_EOL;
        while ($message = openssl_error_string()) {
            $error .= $message . PHP_EOL;
        }

        parent::__construct($error);
    }

    public static function keyCreateError(string $prefix = 'Unable to generate key pair'): self
    {
        return new static($prefix);
    }

    public static function keyExportError(string $prefix = 'Key pair export error'): self
    {
        return new static($prefix);
    }

    public static function csrCreateError(string $prefix = 'Unable to generate CSR'): self
    {
        return new static($prefix);
    }

    public static function csrExportError(string $prefix = 'CSR export error'): self
    {
        return new static($prefix);
    }
}
