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

trait AssertObjectPropertyTrait
{
    public function assertPropertySame($expected, string $attributeName, $actual): void
    {
        static::assertSame($expected, $this->getObjectPropertyValue($actual, $attributeName));
    }

    public function assertPropertyInstanceOf(string $expected, string $attributeName, $actual): void
    {
        static::assertInstanceOf($expected, $this->getObjectPropertyValue($actual, $attributeName));
    }

    public function assertPropertyNull(string $attributeName, $actual): void
    {
        static::assertNull($this->getObjectPropertyValue($actual, $attributeName));
    }

    private function getObjectPropertyValue(object $obj, string $name)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getProperty($name);
        $method->setAccessible(true);

        return $method->getValue($obj);
    }
}
