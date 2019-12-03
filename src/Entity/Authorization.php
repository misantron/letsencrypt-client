<?php

declare(strict_types=1);

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
     * @return Challenge
     *
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
     * @return Challenge
     *
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

    /**
     * @return string
     */
    public function getIdentifierValue(): string
    {
        return $this->identifier['value'];
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public function isIdentifierValueEqual(string $identifier): bool
    {
        return $this->identifier['value'] === $identifier;
    }
}
