<?php

declare(strict_types=1);

namespace LetsEncrypt\Helper;

final class Base64SafeEncoder
{
    public function encode(string $input): string
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    public function decode(string $input): string
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $input .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    public function hashEncode(string $payload): string
    {
        return $this->encode(hash('sha256', $payload, true));
    }
}
