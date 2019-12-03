<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Helper;

use LetsEncrypt\Exception\FileIOException;

final class FileSystem
{
    /**
     * @throws FileIOException
     */
    public static function readFileContent(string $path): string
    {
        try {
            $file = new \SplFileObject($path, 'r');
            $content = $file->fread($file->getSize());
            if ($content === false) {
                throw new \RuntimeException('content read error');
            }

            return $content;
        } catch (\Exception $e) {
            throw FileIOException::readError($path, $e);
        }
    }

    /**
     * @throws FileIOException
     */
    public static function writeFileContent(string $path, string $content): void
    {
        try {
            $len = strlen($content);

            $file = new \SplFileObject($path, 'w');
            $file->rewind();
            if ($len !== $file->fwrite($content)) {
                throw new \RuntimeException('content write error');
            }
            $file->fflush();
        } catch (\Exception $e) {
            throw FileIOException::writeError($path, $e);
        }
    }
}
