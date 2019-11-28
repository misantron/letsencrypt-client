<?php

declare(strict_types=1);

namespace LetsEncrypt\Assertion;

use LetsEncrypt\Exception\EnvironmentException;

final class Assert extends \Webmozart\Assert\Assert
{
    /**
     * Throw EnvironmentException instead of default InvalidArgumentException
     *
     * @param string $message
     */
    protected static function reportInvalidArgument($message): void
    {
        throw new EnvironmentException($message);
    }
}
