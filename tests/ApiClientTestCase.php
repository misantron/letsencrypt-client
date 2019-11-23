<?php

declare(strict_types=1);

namespace LetsEncrypt\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use LetsEncrypt\Http\Connector;

abstract class ApiClientTestCase extends \PHPUnit\Framework\TestCase
{
    use AssertObjectProperty;

    /**
     * @var MockHandler
     */
    private $mockHandler;

    /**
     * @var ClientInterface
     */
    private $httpMockClient;

    /**
     * @var array
     */
    private $history;

    protected function setUp(): void
    {
        parent::setUp();

        $this->history = [];

        $this->mockHandler = new MockHandler();
        $historyMiddleware = Middleware::history($this->history);

        $handlerStack = HandlerStack::create($this->mockHandler);
        $handlerStack->push($historyMiddleware);

        $this->httpMockClient = new Client([
            'handler' => $handlerStack,
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->mockHandler = null;
        $this->httpMockClient = null;
        $this->history = null;
    }

    protected function getHttpClientMock(): ClientInterface
    {
        return $this->httpMockClient;
    }

    protected function getRequestHistory(): array
    {
        return $this->history;
    }

    protected function appendResponseFixture(
        string $name = null,
        int $status = 200,
        array $headers = ['Content-Type' => 'application/json']
    ): void {
        $content = $name === null ? '' : file_get_contents(__DIR__ . '/fixtures/' . $name . '.json');
        $response = new Response($status, $headers, $content);

        $this->mockHandler->append($response);
    }

    protected function createConnector(): Connector
    {
        $this->appendResponseFixture('directory.response');
        $this->appendResponseFixture(null, 200, ['Replay-Nonce' => 'oFvnlFP1wIhRlYS2jTaXbA']);

        return new Connector(true, null, $this->getHttpClientMock());
    }
}
