<?php

declare(strict_types=1);

namespace LetsEncrypt\Helper;

use LetsEncrypt\Exception\FileIOException;

final class FileSystem
{
    /**
     * @param string $path
     * @return string
     *
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
     * @param string $path
     * @param string $content
     *
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
