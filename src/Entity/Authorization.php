<?php

declare(strict_types=1);

namespace LetsEncrypt\Entity;

use LetsEncrypt\Mixin\UrlEntity;

final class Authorization extends Entity
{
    use UrlEntity;

    /**
     * @var array
     */
    public $identifier;

    /**
     * @var string
     */
    public $expires;

    /**
     * @var array
     */
    public $challenges;

    /**
     * @var bool
     */
    public $wildcard;

    /**
     * @param string $type
     * @return Challenge
     * @throws \InvalidArgumentException
     */
    public function getChallenge(string $type): Challenge
    {
        foreach ($this->challenges as $challenge) {
            if ($challenge['type'] === $type) {
                return new Challenge($challenge);
            }
        }
        throw new \InvalidArgumentException('Unknown challenge type provided');
    }
}
