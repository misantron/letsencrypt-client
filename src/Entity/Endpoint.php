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

final class Endpoint extends Entity
{
    /**
     * @var string
     */
    protected $newNonce;

    /**
     * @var string
     */
    protected $newAccount;

    /**
     * @var string
     */
    protected $newOrder;

    /**
     * @var string
     */
    protected $newAuthz;

    /**
     * @var string
     */
    protected $revokeCert;

    /**
     * @var string
     */
    protected $keyChange;

    public function getNewNonceUrl(): string
    {
        return $this->newNonce;
    }

    public function getNewAccountUrl(): string
    {
        return $this->newAccount;
    }

    public function getNewOrderUrl(): string
    {
        return $this->newOrder;
    }

    public function getRevokeCertificateUrl(): string
    {
        return $this->revokeCert;
    }

    public function getKeyChangeUrl(): string
    {
        return $this->keyChange;
    }
}
