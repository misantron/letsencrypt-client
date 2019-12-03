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

final class Endpoint extends Entity
{
    public $newNonce;
    public $newAccount;
    public $newOrder;
    public $newAuthz;
    public $revokeCert;
    public $keyChange;
}
