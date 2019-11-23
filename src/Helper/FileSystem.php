<?php

declare(strict_types=1);

namespace LetsEncrypt\Helper;

use Webmozart\Assert\Assert;

final class FileSystem
{
    public static function readFileContent(string $path): string
    {
        $file = new \SplFileObject($path);
        $content = $file->fread($file->getSize());

        Assert::string($content);

        return $content;
    }

    public static function writeFileContent(string $path, string $content): void
    {
        $file = new \SplFileObject($path);
        $file->rewind();
        $file->fwrite($content);
        $file->fflush();
    }
}