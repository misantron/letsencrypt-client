<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use LetsEncrypt\Helper\Base64SafeEncoder;
use LetsEncrypt\Helper\SignerInterface;
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
        ?array $expectedPayload = null,
        string $expectedUri = null
    ): void {
        $content = $fileName === null ? '' : file_get_contents(__DIR__ . '/fixtures/' . $fileName);
        $response = new Response($status, $headers, $content);

        $this->mockHandler->append(
            function (RequestInterface $request) use ($response, $expectedPayload, $expectedUri) {
                if ($expectedPayload !== null) {
                    $this->assertRequestPayload($request, $expectedPayload);
                }
                if ($expectedUri !== null) {
                    $this->assertRequestUri($request, $expectedUri);
                }

                return $response;
            }
        );
    }

    protected function appendExceptionResponse(
        string $className,
        string $fileName,
        int $status,
        array $headers = ['Content-Type' => 'application/problem+json']
    ): void {
        $request = $this->createMock(RequestInterface::class);

        $content = file_get_contents(__DIR__ . '/fixtures/' . $fileName);
        $response = new Response($status, $headers, $content);

        /** @var TransferException $ex */
        $ex = new $className('Invalid', $request, $response);

        $this->mockHandler->append($ex);
    }

    protected function createConnector(SignerInterface $signer = null): Connector
    {
        $this->appendResponseFixture('directory.response.json');
        $this->appendResponseFixture(null, 200, ['Replay-Nonce' => 'oFvnlFP1wIhRlYS2jTaXbA']);

        return new Connector(true, null, $this->getHttpClientMock(), $signer);
    }

    private function assertRequestPayload(RequestInterface $request, array $expected): void
    {
        $body = json_decode($request->getBody()->getContents(), true);
        $payload = $this->base64Encoder->decode($body['payload']);

        $this->assertIsString($payload);
        $this->assertSame(json_encode($expected), $payload);
    }

    private function assertRequestUri(RequestInterface $request, string $expected): void
    {
        $this->assertSame((string) $request->getUri(), $expected);
    }
}
