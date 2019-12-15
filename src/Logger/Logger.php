<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

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

    public function getLogLevel(ResponseInterface $response): string
    {
        return $response->getStatusCode() < 400 ? LogLevel::INFO : LogLevel::ERROR;
    }
}
