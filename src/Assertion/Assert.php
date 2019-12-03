<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Assertion;

use LetsEncrypt\Exception\EnvironmentException;

final class Assert extends \Webmozart\Assert\Assert
{
    /**
     * Throw EnvironmentException instead of default InvalidArgumentException.
     *
     * @param string $message
     */
    protected static function reportInvalidArgument($message): void
    {
        throw new EnvironmentException($message);
    }
}
