<?php

declare(strict_types=1);

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

    private function getConnector(): Connector
    {
        return $this->connector;
    }
}
