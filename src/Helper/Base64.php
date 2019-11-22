<?php

declare(strict_types=1);

namespace LetsEncrypt\Helper;

final class Base64
{
    public static function urlSafeEncode(string $input): string
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    public static function urlSafeDecode(string $input): string
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $input .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    public static function hashEncode(string $payload): string
    {
        return self::urlSafeEncode(hash('sha256', $payload, true));
    }
}
