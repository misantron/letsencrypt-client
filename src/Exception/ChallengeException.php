<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019-2020
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Exception;

final class ChallengeException extends \LogicException
{
    public function __construct(string $type)
    {
        parent::__construct(ucfirst($type) . ' challenge not found in challenge list');
    }
}
