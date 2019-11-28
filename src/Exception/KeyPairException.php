<?php

declare(strict_types=1);

namespace LetsEncrypt\Exception;

class KeyPairException extends OpenSSLException
{
    public static function privateKeyDetailsError(): self
    {
        return new static('Unable to get details from private key');
    }

    public static function privateKeyInvalid(): self
    {
        return new static('Unable to read private key');
    }
}
