<?php

declare(strict_types=1);

namespace LetsEncrypt\Exception;

final class ChallengeException extends \LogicException
{
    public function __construct(string $type)
    {
        parent::__construct(ucfirst($type) . ' challenge not found in challenge list');
    }
}
