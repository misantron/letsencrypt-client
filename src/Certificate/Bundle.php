<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019-2020
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Certificate;

final class Bundle
{
    public const PRIVATE_KEY = 'private.pem';
    public const PUBLIC_KEY = 'public.pem';
    public const CERTIFICATE = 'certificate.crt';
    public const FULL_CHAIN_CERTIFICATE = 'fullchain.crt';
    public const ORDER = 'order';
}
