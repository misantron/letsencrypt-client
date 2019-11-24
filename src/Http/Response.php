<?php

declare(strict_types=1);

namespace LetsEncrypt\Http;

use Psr\Http\Message\ResponseInterface;

final class Response
{
    private const HEADER_LOCATION = 'Location';

    /**
     * @var ResponseInterface
     */
    private $origin;

    public function __construct(ResponseInterface $origin)
    {
        $this->origin = $origin;
    }

    public function isStatusOk(): bool
    {
        return $this->origin->getStatusCode() === 200;
    }

    public function getDecodedContent(): array
    {
        return json_decode($this->origin->getBody()->getContents(), true);
    }

    public function getRawContent(): string
    {
        return $this->origin->getBody()->getContents();
    }

    public function getLocation(): string
    {
        return $this->origin->getHeaderLine(self::HEADER_LOCATION);
    }
}
