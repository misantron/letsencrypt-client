<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Certificate;

use Spatie\Enum\Enum;

/**
 * Class RevocationReason.
 *
 * @method static self unspecified()
 * @method static self keyCompromise()
 * @method static self affiliationChanged()
 * @method static self superseded()
 * @method static self cessationOfOperation()
 */
final class RevocationReason extends Enum
{
}
