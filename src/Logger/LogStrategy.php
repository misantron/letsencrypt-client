<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Logger;

use Spatie\Enum\Enum;

/**
 * Class LogStrategy.
 *
 * @method static self requestsOnly()
 * @method static self errorsOnly()
 * @method static self debugMode()
 */
final class LogStrategy extends Enum
{
}
