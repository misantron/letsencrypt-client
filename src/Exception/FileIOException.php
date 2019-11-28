<?php

declare(strict_types=1);

namespace LetsEncrypt\Exception;

final class FileIOException extends \RuntimeException
{
    public static function writeError(string $path, \Exception $e): self
    {
        return new static('Unable to write file: ' . $path, 0, $e);
    }

    public static function readError(string $path, \Exception $e): self
    {
        return new static('Unable to read file: ' . $path, 0, $e);
    }
}
