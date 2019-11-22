<?php

declare(strict_types=1);

namespace LetsEncrypt\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockHandler = new MockHandler();
        $this->httpMockClient = new Client([
            'handler' => HandlerStack::create($this->mockHandler),
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->mockHandler = null;
        $this->httpMockClient = null;
    }

    protected function getHttpClientMock(): ClientInterface
    {
        return $this->httpMockClient;
    }

    protected function appendResponseFixture(
        string $name,
        int $status = 200,
        array $headers = ['Content-Type' => 'application/json']
    ): void {
        $content = file_get_contents(__DIR__ . '/fixtures/' . $name . '.json');
        $response = new Response($status, $headers, $content);

        $this->mockHandler->append($response);
    }
}
