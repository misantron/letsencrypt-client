<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019-2020
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Tests;

use LetsEncrypt\Tests\Mixin\EntityMocksTrait;
use LetsEncrypt\Tests\Mixin\ObjectPropertyAssertTrait;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    use EntityMocksTrait;
    use ObjectPropertyAssertTrait;

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
