<?php

declare(strict_types=1);

namespace LetsEncrypt\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use LetsEncrypt\Helper\Base64SafeEncoder;
use LetsEncrypt\Http\Connector;
use Psr\Http\Message\RequestInterface;

abstract class ApiClientTestCase extends TestCase
{
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

    /**
     * @var Base64SafeEncoder
     */
    private $base64Encoder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->base64Encoder = new Base64SafeEncoder();

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

        $this->assertSame(0, $this->mockHandler->count());

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
        string $fileName = null,
        int $status = 200,
        array $headers = ['Content-Type' => 'application/json'],
        array $expectedPayload = null
    ): void {
        $content = $fileName === null ? '' : file_get_contents(__DIR__ . '/fixtures/' . $fileName);
        $response = new Response($status, $headers, $content);

        $this->mockHandler->append(
            function (RequestInterface $request) use ($response, $expectedPayload) {
                if ($expectedPayload !== null) {
                    $this->assertRequestPayload($request, $expectedPayload);
                }

                return $response;
            }
        );
    }

    protected function createConnector(): Connector
    {
        $this->appendResponseFixture('directory.response.json');
        $this->appendResponseFixture(null, 200, ['Replay-Nonce' => 'oFvnlFP1wIhRlYS2jTaXbA']);

        return new Connector(true, null, $this->getHttpClientMock());
    }

    private function assertRequestPayload(RequestInterface $request, array $expected): void
    {
        $body = json_decode($request->getBody()->getContents(), true);
        $payload = $this->base64Encoder->decode($body['payload']);

        $this->assertIsString($payload);
        $this->assertSame(json_encode($expected), $payload);
    }
}
