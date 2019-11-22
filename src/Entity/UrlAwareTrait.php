<?php

declare(strict_types=1);

namespace LetsEncrypt\Entity;

trait UrlAwareTrait
{
    /**
     * @var string
     */
    private $url;

    public function getUrl(): string
    {
        return $this->url;
    }
}
