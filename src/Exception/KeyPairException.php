<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019-2020
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Exception;

final class KeyPairException extends OpenSSLException
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
