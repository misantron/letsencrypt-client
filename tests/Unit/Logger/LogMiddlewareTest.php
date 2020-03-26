<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019-2020
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Tests\Unit\Logger;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use LetsEncrypt\Logger\Logger;
use LetsEncrypt\Logger\LogMiddleware;
use LetsEncrypt\Logger\LogStrategy;
use LetsEncrypt\Tests\TestCase;
use Psr\Log\LogLevel;
use Psr\Log\Test\TestLogger;

class LogMiddlewareTest extends TestCase
{
    /**
     * @var MockHandler
     */
    private $mockHandler;

    /**
     * @var TestLogger
     */
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockHandler = new MockHandler();
        $this->logger = new TestLogger();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->mockHandler = null;
        $this->logger = null;
    }

    private function createTestClient(LogStrategy $strategy, array $options = []): Client
    {
        $logger = new Logger($this->logger, $strategy);
        $middleware = new LogMiddleware($logger);

        $stack = HandlerStack::create($this->mockHandler);
        $stack->unshift($middleware);

        return new Client(
            array_merge(
                [
                    'handler' => $stack,
                ],
                $options
            )
        );
    }

    private function appendResponse(int $code = 200, array $headers = [], string $body = ''): void
    {
        $this->mockHandler->append(new Response($code, $headers, $body));
    }

    private function appendException(TransferException $exception): void
    {
        $this->mockHandler->append($exception);
    }

    public function testLogWithOnlyRequestsStrategy(): void
    {
        $this->appendResponse(200);
        $this->appendResponse(403);
        $this->appendResponse(500);

        $client = $this->createTestClient(LogStrategy::requestsOnly(), [RequestOptions::HTTP_ERRORS => false]);

        $client->get('/');
        $client->get('/foo');
        $client->get('/bar');

        $this->assertCount(3, $this->logger->records);

        $this->assertSame(LogLevel::INFO, $this->logger->records[0]['level']);
        $this->assertSame('GET', $this->logger->records[0]['context']['method']);
        $this->assertSame('/', $this->logger->records[0]['context']['url']);

        $this->assertSame(LogLevel::INFO, $this->logger->records[1]['level']);
        $this->assertSame('GET', $this->logger->records[1]['context']['method']);
        $this->assertSame('/foo', $this->logger->records[1]['context']['url']);

        $this->assertSame(LogLevel::INFO, $this->logger->records[2]['level']);
        $this->assertSame('GET', $this->logger->records[2]['context']['method']);
        $this->assertSame('/bar', $this->logger->records[2]['context']['url']);
    }

    public function testLogWithErrorsOnlyStrategy(): void
    {
        $this->appendResponse(200);
        $this->appendResponse(302);
        $this->appendResponse(403, [], 'access-denied');
        $this->appendException(new ServerException('Internal error', new Request('GET', '/bar')));

        $client = $this->createTestClient(LogStrategy::errorsOnly());

        $client->get('/');
        $client->get('/baz');

        try {
            $client->get('/foo');
        } catch (TransferException $e) {
            // ignore exception
        }
        try {
            $client->get('/bar');
        } catch (TransferException $e) {
            // ignore exception
        }

        $this->assertCount(2, $this->logger->records);

        $this->assertSame(LogLevel::ERROR, $this->logger->records[0]['level']);
        $this->assertSame('GET', $this->logger->records[0]['context']['method']);
        $this->assertSame('/foo', $this->logger->records[0]['context']['url']);
        $this->assertSame(403, $this->logger->records[0]['context']['status']);
        $this->assertSame('access-denied', $this->logger->records[0]['context']['body']);

        $this->assertSame(LogLevel::CRITICAL, $this->logger->records[1]['level']);
        $this->assertSame('GET', $this->logger->records[1]['context']['method']);
        $this->assertSame('/bar', $this->logger->records[1]['context']['url']);
        $this->assertArrayHasKey('reason', $this->logger->records[1]['context']);
        $this->assertArrayNotHasKey('status', $this->logger->records[1]['context']);
        $this->assertArrayNotHasKey('body', $this->logger->records[1]['context']);
    }

    public function testLogWithDebugModeStrategy(): void
    {
        $this->appendResponse(201);
        $this->appendResponse(200);
        $this->appendResponse(403, [], 'access-denied');
        $this->appendException(new ServerException('Internal error', new Request('GET', '/bar')));

        $client = $this->createTestClient(LogStrategy::debugMode());

        $client->get('/');
        $client->get('/baz');

        try {
            $client->get('/foo');
        } catch (TransferException $e) {
            // ignore exception
        }
        try {
            $client->get('/bar');
        } catch (TransferException $e) {
            // ignore exception
        }

        $this->assertCount(4, $this->logger->records);

        $this->assertSame(LogLevel::INFO, $this->logger->records[0]['level']);
        $this->assertSame('GET', $this->logger->records[0]['context']['method']);
        $this->assertSame('/', $this->logger->records[0]['context']['url']);
        $this->assertSame(201, $this->logger->records[0]['context']['status']);
        $this->assertSame('', $this->logger->records[0]['context']['body']);

        $this->assertSame(LogLevel::INFO, $this->logger->records[1]['level']);
        $this->assertSame('GET', $this->logger->records[1]['context']['method']);
        $this->assertSame('/baz', $this->logger->records[1]['context']['url']);
        $this->assertSame(200, $this->logger->records[1]['context']['status']);
        $this->assertSame('', $this->logger->records[1]['context']['body']);

        $this->assertSame(LogLevel::ERROR, $this->logger->records[2]['level']);
        $this->assertSame('GET', $this->logger->records[2]['context']['method']);
        $this->assertSame('/foo', $this->logger->records[2]['context']['url']);
        $this->assertSame(403, $this->logger->records[2]['context']['status']);
        $this->assertSame('access-denied', $this->logger->records[2]['context']['body']);

        $this->assertSame(LogLevel::CRITICAL, $this->logger->records[3]['level']);
        $this->assertSame('GET', $this->logger->records[3]['context']['method']);
        $this->assertSame('/bar', $this->logger->records[3]['context']['url']);
        $this->assertArrayNotHasKey('status', $this->logger->records[3]['context']);
        $this->assertArrayNotHasKey('body', $this->logger->records[3]['context']);
    }
}
