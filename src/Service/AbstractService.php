<?php

declare(strict_types=1);

namespace LetsEncrypt\Service;

use LetsEncrypt\Http\Connector;

abstract class AbstractService
{
    /**
     * @var Connector
     */
    private $connector;

    public function __construct(Connector $connector)
    {
        $this->connector = $connector;
    }

    protected function getConnector(): Connector
    {
        return $this->connector;
    }
}
