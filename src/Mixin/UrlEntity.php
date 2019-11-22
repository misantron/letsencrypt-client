<?php

declare(strict_types=1);

namespace LetsEncrypt\Mixin;

trait UrlEntity
{
    /**
     * @var string
     */
    private $url;

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
