<?php

declare(strict_types=1);

namespace LetsEncrypt\Entity;

final class Challenge extends Entity
{
    use UrlAwareTrait;

    private const TYPE_HTTP = 'http-01';
    private const TYPE_DNS = 'dns-01';

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    public $validated;

    /**
     * @var string
     */
    private $token;

    public function isDns(): bool
    {
        return $this->type === self::TYPE_DNS;
    }

    public function isHttp(): bool
    {
        return $this->type === self::TYPE_HTTP;
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
