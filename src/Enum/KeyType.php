<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019-2020
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Enum;

use Spatie\Enum\Enum;

/**
 * Class KeyType.
 *
 * @method static self rsa()
 * @method static self ec()
 */
final class KeyType extends Enum
{
}
