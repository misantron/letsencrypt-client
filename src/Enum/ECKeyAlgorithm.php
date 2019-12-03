<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Enum;

use Spatie\Enum\Enum;

/**
 * Class ECKeyType.
 *
 * @method static self prime256v1()
 * @method static self secp384r1()
 */
final class ECKeyAlgorithm extends Enum
{
}
