<?php

declare(strict_types=1);

namespace LetsEncrypt\Http;

use Spatie\Enum\Enum;

/**
 * Class LogStrategy
 * @package LetsEncrypt\Http
 *
 * @method static self requestsOnly()
 * @method static self errorsOnly()
 * @method static self debugMode()
 */
final class LogStrategy extends Enum
{

}
