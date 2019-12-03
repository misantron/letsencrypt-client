<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

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
