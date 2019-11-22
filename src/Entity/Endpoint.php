<?php

declare(strict_types=1);

namespace LetsEncrypt\Entity;

final class Endpoint extends Entity
{
    public $newNonce;
    public $newAccount;
    public $newOrder;
    public $newAuthz;
    public $revokeCert;
    public $keyChange;
}
