<?php

declare(strict_types=1);

namespace LetsEncrypt\Http;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use LetsEncrypt\Entity\Endpoint;
use LetsEncrypt\Helper\Signer;
use Psr\Http\Message\ResponseInterface;

final class Connector
{
    private const STAGING_BASE_URL = 'https://acme-staging-v02.api.letsencrypt.org';
    private const PRODUCTION_BASE_URL = 'https://acme-v02.api.letsencrypt.org';

    private const HEADER_NONCE = 'Replay-Nonce';

    private const DIRECTORY_ENDPOINT = 'directory';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Endpoint
     */
    private $endpoint;

    /**
     * @var string
     */
    private $nonce;

    public function __construct(bool $staging, Logger $logger = null, ClientInterface $client = null)
    {
        $this->client = $client ?? $this->createClient($staging, $logger);

        $this->getEndpoints();
    }

    private function createClient(bool $staging, Logger $logger = null): ClientInterface
    {
        $handlerStack = HandlerStack::create();
        if ($logger instanceof Logger) {
            $handlerStack->push(new LogMiddleware($logger));
        }

        return new Client([
            'base_uri' => $staging ? self::STAGING_BASE_URL : self::PRODUCTION_BASE_URL,
            'handler' => $handlerStack,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function getNewAccountEndpoint(): string
    {
        return $this->endpoint->newAccount;
    }

    public function getNewOrderEndpoint(): string
    {
        return $this->endpoint->newOrder;
    }

    public function getKeyChangeEndpoint(): string
    {
        return $this->endpoint->keyChange;
    }

    /**
     * @param string $url
     * @param array $payload
     * @param string $privateKeyPath
     * @return Response
     */
    public function requestWithJWKSigned(string $url, array $payload, string $privateKeyPath): Response
    {
        $sign = Signer::jwk($payload, $url, $this->nonce, $privateKeyPath);

        return $this->request('POST', $url, $sign);
    }

    /**
     * @param string $kid
     * @param string $url
     * @param array $payload
     * @param string $privateKeyPath
     * @return Response
     */
    public function requestWithKIDSigned(string $kid, string $url, array $payload, string $privateKeyPath): Response
    {
        $sign = Signer::kid($payload, $kid, $url, $this->nonce, $privateKeyPath);

        return $this->request('POST', $url, $sign);
    }

    public function get(string $uri): Response
    {
        return $this->request('GET', $uri);
    }

    private function getEndpoints(): void
    {
        $response = $this->request('GET', self::DIRECTORY_ENDPOINT);

        $this->endpoint = new Endpoint($response->getPayload());
    }

    private function getNonce(): void
    {
        $this->request('HEAD', $this->endpoint->newNonce);
    }

    private function request(string $method, string $uri, array $data = null): Response
    {
        $options = [
            RequestOptions::HEADERS => [
                'Content-Type' => $method === 'POST' ? 'application/jose+json' : 'application/json',
            ],
            RequestOptions::ON_HEADERS => function (ResponseInterface $response) use ($method) {
                if ($response->hasHeader(self::HEADER_NONCE)) {
                    $this->nonce = $response->getHeaderLine(self::HEADER_NONCE);
                } elseif ($method === 'POST') {
                    $this->getNonce();
                }
            },
        ];

        if ($data !== null) {
            $options[RequestOptions::JSON] = $data;
        }

        try {
            $response = $this->client->request($method, $uri, $options);
        } catch (ClientException $e) {
            $response = $e->getResponse();
        }

        return new Response($response);
    }
}
