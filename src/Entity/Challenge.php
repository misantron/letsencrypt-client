<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019-2020
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Entity;

final class Challenge extends Entity
{
    use UrlAwareTrait;

    private const TYPE_HTTP = 'http-01';
    private const TYPE_DNS = 'dns-01';

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    public $validated;

    /**
     * @var string
     */
    protected $token;

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
