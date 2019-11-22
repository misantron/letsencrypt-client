<?php

declare(strict_types=1);

namespace LetsEncrypt\Entity;

final class Challenge extends Entity
{
    use UrlAwareTrait;

    private const TYPE_HTTP = 'http-01';
    private const TYPE_DNS = 'dns-01';

    public const DNS_VERIFY_URI = 'https://dns.google.com/resolve';

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $validated;

    /**
     * @var string
     */
    public $token;

    public function isDns(): bool
    {
        return $this->type === self::TYPE_DNS;
    }

    public function isHttp(): bool
    {
        return $this->type === self::TYPE_HTTP;
    }
}
