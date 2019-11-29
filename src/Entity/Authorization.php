<?php

declare(strict_types=1);

namespace LetsEncrypt\Entity;

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
     * @var array
     */
    protected $challenges;

    /**
     * @var bool
     */
    public $wildcard;

    public function __construct(array $data, string $url)
    {
        parent::__construct($data);

        $this->url = $url;
    }

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

    /**
     * @return Identifier
     */
    public function getIdentifier(): Identifier
    {
        return new Identifier($this->identifier);
    }
}
