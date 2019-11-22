<?php

declare(strict_types=1);

namespace LetsEncrypt\Entity;

use LetsEncrypt\Mixin\UrlEntity;

final class Account extends Entity
{
    use UrlEntity;

    public $id;
    public $key;
    public $contact;
    public $agreement;
    public $initialIp;
    public $createdAt;

    public function getPrivateKeyPath(): string
    {

    }
}
