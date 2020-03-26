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

final class Account extends Entity
{
    use UrlAwareTrait;

    private const STATUS_DEACTIVATED = 'deactivated';

    public $id;

    /**
     * @var array
     */
    public $key;

    /**
     * @var array
     */
    public $contact;

    public $agreement;

    /**
     * @var string
     */
    public $initialIp;

    /**
     * @var string
     */
    public $createdAt;

    /**
     * @var string
     */
    private $keyPath;

    public function __construct(array $data, string $url, string $keyPath)
    {
        parent::__construct($data);

        $this->url = $url;
        $this->keyPath = $keyPath;
    }

    public function getPrivateKeyPath(): string
    {
        return $this->keyPath;
    }

    public function isDeactivated(): bool
    {
        return $this->status === self::STATUS_DEACTIVATED;
    }
}
