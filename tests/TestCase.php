<?php

declare(strict_types=1);

namespace LetsEncrypt\Tests;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    use AssertObjectPropertyTrait;

    /**
     * @var string
     */
    private static $keysPath;

    protected static function getKeysPath(): string
    {
        if (self::$keysPath === null) {
            self::$keysPath = __DIR__ . '/keys/';
        }

        return self::$keysPath;
    }
}
