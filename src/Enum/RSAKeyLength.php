<?php

declare(strict_types=1);

namespace LetsEncrypt\Enum;

use Spatie\Enum\Enum;

class RSAKeyLength extends Enum
{
    public static function bit2048(): self
    {
        return new class() extends RSAKeyLength {
            public function getValue(): string
            {
                return '2048';
            }
        };
    }

    public static function bit4096(): self
    {
        return new class() extends RSAKeyLength {
            public function getValue(): string
            {
                return '4096';
            }
        };
    }
}
