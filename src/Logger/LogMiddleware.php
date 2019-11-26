<?php

declare(strict_types=1);

namespace LetsEncrypt\Logger;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LogLevel;

use function GuzzleHttp\Promise\rejection_for;

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
                $level = $this->logger->getLogLevel($reason->getResponse());
                $context = [
                    'method' => $request->getMethod(),
                    'url' => (string) $request->getUri(),
                ];
                if ($reason->hasResponse()) {
                    /** @var ResponseInterface $response */
                    $response = $reason->getResponse();
                    $context['status'] = $response->getStatusCode();
                    $context['headers'] = json_encode($response->getHeaders(), JSON_PRETTY_PRINT);
                    $context['body'] = $response->getBody()->getContents();
                }
                $this->logger->log($level, 'failed request', $context);
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
