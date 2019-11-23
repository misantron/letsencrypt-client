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
use LetsEncrypt\Helper\SignerInterface;
use LetsEncrypt\Logger\Logger;
use LetsEncrypt\Logger\LogMiddleware;
use Psr\Http\Message\ResponseInterface;

final class Connector
{
    private const STAGING_BASE_URL = 'https://acme-staging-v02.api.letsencrypt.org';
    private const PRODUCTION_BASE_URL = 'https://acme-v02.api.letsencrypt.org';

    private const HEADER_NONCE = 'Replay-Nonce';

    private const DIRECTORY_ENDPOINT = 'directory';

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var SignerInterface
     */
    private $signer;

    /**
     * @var Endpoint
     */
    private $endpoint;

    /**
     * @var string
     */
    private $nonce;

    public function __construct(
        bool $staging,
        Logger $logger = null,
        ClientInterface $client = null,
        SignerInterface $signer = null
    ) {
        $this->client = $client ?? $this->createClient($staging, $logger);
        $this->signer = $signer ?? Signer::createWithBase64SafeEncoder();

        $this->getEndpoints();
        $this->getNonce();
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

    public function getSigner(): Signer
    {
        return $this->signer;
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
    public function signedJWKRequest(string $url, array $payload, string $privateKeyPath): Response
    {
        $sign = $this->signer->jwk($payload, $url, $this->nonce, $privateKeyPath);

        return $this->request('POST', $url, $sign);
    }

    /**
     * @param string $kid
     * @param string $url
     * @param array $payload
     * @param string $privateKeyPath
     * @return Response
     */
    public function signedKIDRequest(string $kid, string $url, array $payload, string $privateKeyPath): Response
    {
        $sign = $this->signer->kid($payload, $kid, $url, $this->nonce, $privateKeyPath);

        return $this->request('POST', $url, $sign);
    }

    public function get(string $uri): Response
    {
        return $this->request('GET', $uri);
    }

    private function getEndpoints(): void
    {
        $response = $this->request('GET', self::DIRECTORY_ENDPOINT);

        $this->endpoint = new Endpoint($response->getDecodedContent());
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
