<?php

declare(strict_types=1);

namespace LetsEncrypt\Assert;

use LetsEncrypt\Exception\EnvironmentException;

final class Assert
{
    /**
     * @param string $path
     * @throws EnvironmentException
     */
    public static function directoryExists(string $path): void
    {
        if (!is_dir($path)) {
            throw EnvironmentException::directoryNotExists($path);
        }
    }

    /**
     * @param string $path
     * @throws EnvironmentException
     */
    public static function fileExists(string $path): void
    {
        if (!file_exists($path)) {
            throw EnvironmentException::fileNotExists($path);
        }
    }
}
