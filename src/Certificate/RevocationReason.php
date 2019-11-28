<?php

declare(strict_types=1);

namespace LetsEncrypt\Certificate;

use Spatie\Enum\Enum;

/**
 * Class RevocationReason
 * @package LetsEncrypt\Certificate
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
