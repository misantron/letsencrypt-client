<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019-2020
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

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
        $this->getNewNonce();
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

    public function getSigner(): SignerInterface
    {
        return $this->signer;
    }

    public function getNewAccountUrl(): string
    {
        return $this->endpoint->getNewAccountUrl();
    }

    public function getNewOrderUrl(): string
    {
        return $this->endpoint->getNewOrderUrl();
    }

    public function getRevokeCertificateUrl(): string
    {
        return $this->endpoint->getRevokeCertificateUrl();
    }

    public function getAccountKeyChangeUrl(): string
    {
        return $this->endpoint->getKeyChangeUrl();
    }

    public function signedJWSRequest(string $url, array $payload, string $privateKeyPath): Response
    {
        $sign = $this->signer->jws($payload, $url, $this->nonce, $privateKeyPath);

        return $this->request('POST', $url, $sign);
    }

    public function signedJWS(string $url, array $payload, string $privateKeyPath): array
    {
        return $this->signer->jws($payload, $url, $this->nonce, $privateKeyPath);
    }

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

    private function getNewNonce(): void
    {
        $this->request('HEAD', $this->endpoint->getNewNonceUrl());
    }

    private function request(string $method, string $uri, array $data = null): Response
    {
        $options = [
            RequestOptions::HEADERS => [
                'Content-Type' => $method === 'POST' ? 'application/jose+json' : 'application/json',
            ],
        ];

        if ($data !== null) {
            $options[RequestOptions::JSON] = $data;
        }

        try {
            $response = $this->client->request($method, $uri, $options);

            $this->updateNonce($method, $response);
        } catch (ClientException $e) {
            $response = $e->getResponse();
        }

//        var_dump([
//            'method' => $method,
//            'uri' => $uri,
//            'status' => $response->getStatusCode(),
//            'headers' => json_encode($response->getHeaders(), JSON_PRETTY_PRINT),
//            'content' => $response->getBody()->getContents(),
//        ]);

        return new Response($response);
    }

    private function updateNonce(string $method, ResponseInterface $response): void
    {
        if ($response->hasHeader(self::HEADER_NONCE)) {
            $this->nonce = $response->getHeaderLine(self::HEADER_NONCE);
        } elseif ($method === 'POST') {
            $this->getNewNonce();
        }
    }
}
