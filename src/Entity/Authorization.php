<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Entity;

use LetsEncrypt\Exception\ChallengeException;

final class Authorization extends Entity
{
    use UrlAwareTrait;

    /**
     * @var array
     */
    protected $identifier;

    /**
     * @var string
     */
    public $expires;

    /**
     * @var Challenge[]
     */
    protected $challenges;

    /**
     * @var bool
     */
    public $wildcard;

    public function __construct(array $data, string $url)
    {
        $data['challenges'] = array_map(static function (array $entry) {
            return new Challenge($entry);
        }, $data['challenges']);

        parent::__construct($data);

        $this->url = $url;
    }

    /**
     * @throws ChallengeException
     */
    public function getHttpChallenge(): Challenge
    {
        foreach ($this->challenges as $challenge) {
            if ($challenge->isHttp()) {
                return $challenge;
            }
        }
        throw new ChallengeException('http');
    }

    /**
     * @throws ChallengeException
     */
    public function getDnsChallenge(): Challenge
    {
        foreach ($this->challenges as $challenge) {
            if ($challenge->isDns()) {
                return $challenge;
            }
        }
        throw new ChallengeException('dns');
    }

    public function getIdentifierValue(): string
    {
        return $this->identifier['value'];
    }

    public function isIdentifierValueEqual(string $identifier): bool
    {
        return $this->identifier['value'] === $identifier;
    }
}
