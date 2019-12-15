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

use GuzzleHttp\Exception\RequestException;
use function GuzzleHttp\Promise\rejection_for;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LogLevel;

final class LogMiddleware
{
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            if ($this->logger->logRequestsOnly()) {
                $this->logRequest($request);
            }

            return $handler($request, $options)
                ->then(
                    $this->handleSuccess($request),
                    $this->handleFailure($request)
                );
        };
    }

    private function handleSuccess(RequestInterface $request): callable
    {
        return function (ResponseInterface $response) use ($request) {
            if ($this->logger->debugMode()) {
                $level = $this->logger->getLogLevel($response);
                $context = [
                    'method' => $request->getMethod(),
                    'url' => (string) $request->getUri(),
                    'status' => $response->getStatusCode(),
                    'headers' => json_encode($response->getHeaders(), JSON_PRETTY_PRINT),
                    'body' => $response->getBody()->getContents(),
                ];
                $this->logger->log($level, 'succeed request', $context);
            }

            return $response;
        };
    }

    private function handleFailure(RequestInterface $request): callable
    {
        return function (\Exception $reason) use ($request) {
            if ($this->logger->logErrorsOnly() || $this->logger->debugMode()) {
                if ($reason instanceof RequestException && $reason->hasResponse()) {
                    /** @var ResponseInterface $response */
                    $response = $reason->getResponse();
                    $context = [
                        'method' => $request->getMethod(),
                        'url' => (string) $request->getUri(),
                        'status' => $response->getStatusCode(),
                        'headers' => json_encode($response->getHeaders(), JSON_PRETTY_PRINT),
                        'body' => $response->getBody()->getContents(),
                    ];
                    $level = $this->logger->getLogLevel($response);
                    $this->logger->log($level, 'failed request', $context);
                } else {
                    $context = [
                        'method' => $request->getMethod(),
                        'url' => (string) $request->getUri(),
                        'reason' => [
                            'code' => $reason->getCode(),
                            'message' => $reason->getMessage(),
                            'trace' => $reason->getTrace(),
                        ],
                    ];
                    $this->logger->log(LogLevel::CRITICAL, 'invalid request', $context);
                }
            }

            return rejection_for($reason);
        };
    }

    private function logRequest(RequestInterface $request): void
    {
        $this->logger->log(LogLevel::INFO, 'request', [
            'method' => $request->getMethod(),
            'url' => (string) $request->getUri(),
            'headers' => $request->getHeaders(),
        ]);
    }
}
