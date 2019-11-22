<?php

declare(strict_types=1);

namespace LetsEncrypt\Http;

use Psr\Http\Message\ResponseInterface;

final class Response
{
    private const HEADER_LOCATION = 'Location';

    private const HTTP_STATUS_OK = 200;

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
        return $this->origin->getStatusCode() === self::HTTP_STATUS_OK;
    }

    public function getPayload(): array
    {
        return json_decode($this->origin->getBody()->getContents(), true);
    }

    public function getRawBody(): string
    {
        return $this->origin->getBody()->getContents();
    }

    public function getLocation(): string
    {
        return $this->origin->getHeaderLine(self::HEADER_LOCATION);
    }
}
