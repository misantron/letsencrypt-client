<?php

declare(strict_types=1);

namespace LetsEncrypt\Certificate;

final class File
{
    public const PRIVATE_KEY = 'private.pem';
    public const PUBLIC_KEY = 'public.pem';
    public const CERTIFICATE = 'certificate.crt';
    public const FULL_CHAIN_CERTIFICATE = 'fullchain.crt';
    public const ORDER = 'order';
}
