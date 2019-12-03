<?php

declare(strict_types=1);

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
