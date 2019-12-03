<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Http;

trait ConnectorAwareTrait
{
    /**
     * @var Connector
     */
    private $connector;

    public function setConnector(Connector $connector): self
    {
        $this->connector = $connector;

        return $this;
    }
}
