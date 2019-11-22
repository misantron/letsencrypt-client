<?php

declare(strict_types=1);

namespace LetsEncrypt\Entity;

final class Account extends Entity
{
    use UrlAwareTrait;

    public $id;
    public $key;
    public $contact;
    public $agreement;
    public $initialIp;
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
}
