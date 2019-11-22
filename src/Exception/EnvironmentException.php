<?php

declare(strict_types=1);

namespace LetsEncrypt\Exception;

class EnvironmentException extends \RuntimeException
{
    public static function fileNotExists(string $path): self
    {
        return new static('File does not exist: ' . $path);
    }

    public static function directoryNotExists(string $path): self
    {
        return new static('Directory does not exist: ' . $path);
    }
}
