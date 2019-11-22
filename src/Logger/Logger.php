<?php

declare(strict_types=1);

namespace LetsEncrypt\Logger;

use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class Logger
{
    /**
     * @var LoggerInterface
     */
    private $origin;

    /**
     * @var LogStrategy
     */
    private $strategy;

    public function __construct(LoggerInterface $origin, LogStrategy $strategy = null)
    {
        if ($strategy === null) {
            $strategy = LogStrategy::errorsOnly();
        }

        $this->origin = $origin;
        $this->strategy = $strategy;
    }

    public function log(string $level, string $message, array $context): void
    {
        $this->origin->log($level, $message, $context);
    }

    public function logRequestsOnly(): bool
    {
        return $this->strategy->isEqual('requestsOnly');
    }

    public function logErrorsOnly(): bool
    {
        return $this->strategy->isEqual('errorsOnly');
    }

    public function debugMode(): bool
    {
        return $this->strategy->isEqual('debugMode');
    }

    public function getLogLevel(ResponseInterface $response = null): string
    {
        if ($response === null) {
            return LogLevel::CRITICAL;
        }
        $statusCode = $response->getStatusCode();
        switch (true) {
            case $statusCode < 300:
                return LogLevel::INFO;
            case $statusCode < 400:
                return LogLevel::WARNING;
            default:
                return LogLevel::ERROR;
        }
    }
}
