<?php

declare(strict_types=1);

namespace LetsEncrypt\Service;

use LetsEncrypt\Assert\Assert;
use LetsEncrypt\Certificate\Certificate;
use LetsEncrypt\Certificate\File;
use LetsEncrypt\Entity\Account;
use LetsEncrypt\Entity\Authorization;
use LetsEncrypt\Entity\Challenge;
use LetsEncrypt\Entity\Order;
use LetsEncrypt\Helper\Base64;
use LetsEncrypt\Helper\Key;
use LetsEncrypt\Http\Connector;

class OrderService extends AbstractService
{
    /**
     * @var string
     */
    private $filesPath;

    public function __construct(Connector $connector, string $filesPath)
    {
        parent::__construct($connector);

        Assert::directoryExists($filesPath);

        $this->filesPath = $filesPath;
    }

    public function create(Account $account, string $basename, array $subjects, Certificate $certificate): Order
    {
        $certificateBasePath = $this->getCertificateBasePath($basename);

        if (!mkdir($certificateBasePath, 0755) && !is_dir($certificateBasePath)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $certificateBasePath));
        }

        $identifiers = array_map(static function (string $subject) {
            return [
                'type' => 'dns',
                'value' => $subject,
            ];
        }, $subjects);

        $payload = [
            'identifiers' => $identifiers,
            'notBefore' => $certificate->getNotBefore(),
            'notAfter' => $certificate->getNotAfter(),
        ];

        $response = $this->getConnector()->requestWithKIDSigned(
            $account->getUrl(),
            $this->getConnector()->getEndpoint()->newOrder,
            $payload,
            $account->getPrivateKeyPath()
        );

        if (!$response->isStatusCreated()) {
            $this->cleanupFiles($basename);
            throw new \RuntimeException();
        }

        $orderUrl = $response->getLocation();
        file_put_contents($this->getOrderFilePath($basename), $orderUrl);

        if ($certificate->getKey()->isRSA()) {
            Key::rsa(
                $this->getPrivateKeyPath($basename),
                $this->getPublicKeyPath($basename),
                $certificate->getKey()->getLength()
            );
        } elseif ($certificate->getKey()->isEC()) {
            Key::ec(
                $this->getPrivateKeyPath($basename),
                $this->getPublicKeyPath($basename),
                $certificate->getKey()->getAlgorithm()
            );
        }

        $order = new Order($response->getPayload());

        $authorizations = $this->getAuthorizations($order->authorizations);
        $order->setAuthorizationsData($authorizations);

        return $order;
    }

    public function get(string $basename, array $subjects): Order
    {
        $orderFilePath = $this->getOrderFilePath($basename);
        Assert::fileExists($orderFilePath);

        $orderUrl = file_get_contents($orderFilePath);

        $response = $this->getConnector()->get($orderUrl);

        $order = new Order($response->getPayload());
        if ($order->isInvalid()) {
            throw new \RuntimeException('Order has invalid status');
        }
        if (!$order->isIdentifiersEqual($subjects)) {
            throw new \RuntimeException('Order data is invalid - subjects are not equal');
        }

        $authorizations = $this->getAuthorizations($order->authorizations);
        $order->setAuthorizationsData($authorizations);

        return $order;
    }

    public function getOrCreate(Account $account, string $basename, array $subjects, Certificate $certificate)
    {
        try {
            $order = $this->get($basename, $subjects);
        } catch (\RuntimeException $e) {
            $this->cleanupFiles($basename);
            $order = $this->create($account, $basename, $subjects, $certificate);
        }

        return $order;
    }

    public function getPendingAuthorizations(string $type, Account $account, Order $order): array
    {
        $authorizations = [];

        $privateKey = openssl_pkey_get_private('file://' . $account->getPrivateKeyPath());
        if ($privateKey === false) {

        }
        $details = openssl_pkey_get_details($privateKey);
        if ($details === false) {

        }

        $header = [
            'e' => Base64::urlSafeEncode($details['rsa']['e']),
            'kty' => 'RSA',
            'n' => Base64::urlSafeEncode($details['rsa']['n']),
        ];
        $digest = Base64::hashEncode(json_encode($header));

        foreach ($order->getPendingAuthorizations() as $authorization) {
            $challenge = $authorization->getChallenge($type);
            if ($challenge->isPending()) {
                $keyAuthorization = $challenge->token . '.' . $digest;
                switch (true) {
                    case $challenge->isHttp():
                        $authorizations[] = [
                            'type' => $type,
                            'identifier' => $authorization->identifier['value'],
                            'filename' => $challenge->token,
                            'content' => $keyAuthorization,
                        ];
                        break;
                    case $challenge->isDns():
                        $dnsDigest = Base64::hashEncode($keyAuthorization);
                        $authorizations[] = [
                            'type' => $type,
                            'identifier' => $authorization->identifier['value'],
                            'DNSDigest' => $dnsDigest,
                        ];
                        break;
                }
            }
        }

        return $authorizations;
    }

    public function verifyPendingAuthorization(string $identifier, string $type, Account $account, Order $order): bool
    {
        $privateKey = openssl_pkey_get_private('file://' . $account->getPrivateKeyPath());
        if ($privateKey === false) {

        }
        $details = openssl_pkey_get_details($privateKey);
        if ($details === false) {

        }

        $header = [
            'e' => Base64::urlSafeEncode($details['rsa']['e']),
            'kty' => 'RSA',
            'n' => Base64::urlSafeEncode($details['rsa']['n']),
        ];
        $digest = Base64::hashEncode(json_encode($header));

        foreach ($order->getAuthorizations() as $authorization) {
            if ($authorization->identifier['value'] === $identifier && $authorization->isPending()) {
                $challenge = $authorization->getChallenge($type);
                if ($challenge->isPending()) {
                    $keyAuthorization = $challenge->token . '.' . $digest;

                    switch (true) {
                        case $challenge->isHttp():
                            if ($this->verifyHttpChallenge($identifier, $challenge->token, $keyAuthorization)) {
                                $payload = [
                                    'keyAuthorization' => $keyAuthorization,
                                ];
                                $response = $this->getConnector()->requestWithKIDSigned(
                                    $account->getUrl(),
                                    $challenge->getUrl(),
                                    $payload,
                                    $account->getPrivateKeyPath()
                                );
                                if ($response->isStatusOk()) {
                                    while ($authorization->isPending()) {
                                        sleep(1);
                                        $authorization = $this->updateAuthorization($authorization->getUrl());
                                    }
                                    return true;
                                }
                            }
                            break;
                        case $challenge->isDns():
                            $dnsDigest = Base64::hashEncode($keyAuthorization);
                            if ($this->verifyDnsChallenge($identifier, $dnsDigest)) {
                                $payload = [
                                    'keyAuthorization' => $keyAuthorization,
                                ];
                                $response = $this->getConnector()->requestWithKIDSigned(
                                    $account->getUrl(),
                                    $challenge->getUrl(),
                                    $payload,
                                    $account->getPrivateKeyPath()
                                );
                                if ($response->isStatusOk()) {
                                    while ($authorization->isPending()) {
                                        sleep(1);
                                        $authorization = $this->updateAuthorization($authorization->getUrl());
                                    }
                                    return true;
                                }
                            }
                            break;
                    }
                }
            }
        }

        return false;
    }

    public function finalize(Account $account, Order $order, string $csr = ''): Order
    {
        if (!$order->isPending() && !$order->isReady()) {

        }
        if (!$order->allAuthorizationsValid()) {

        }
        $payload = [
            'csr' => Base64::urlSafeEncode(base64_decode($csr)),
        ];

        $response = $this->getConnector()->requestWithKIDSigned(
            $account->getUrl(),
            $order->finalize,
            $payload,
            $account->getPrivateKeyPath()
        );

        $order = new Order($response->getPayload());

        $authorizations = $this->getAuthorizations($order->authorizations);
        $order->setAuthorizationsData($authorizations);

        return $order;
    }

    private function getAuthorizations(array $urls): array
    {
        $authorizations = [];
        foreach ($urls as $url) {
            $authorizations[] = $this->updateAuthorization($url);
        }
        return $authorizations;
    }

    private function updateAuthorization(string $url): Authorization
    {
        $response = $this->getConnector()->get($url);

        $authorization = new Authorization($response->getPayload());
        $authorization->setUrl($url);

        return $authorization;
    }

    private function verifyHttpChallenge(string $domain, string $token, string $key): bool
    {
        $response = $this->getConnector()->get($domain . '/.well-known/acme-challenge/' . $token);

        return $response->getRawBody() === $key;
    }

    private function verifyDnsChallenge(string $domain, string $dnsDigest): bool
    {
        $query = [
            'type' => 'TXT',
            'name' => '_acme-challenge.' . $domain,
        ];

        $response = $this->getConnector()->get(Challenge::DNS_VERIFY_URI . '?' . http_build_query($query));
        $data = $response->getPayload();

        if ($data['Status'] === 0 && isset($data['Answer'])) {
            foreach ($data['Answer'] as $answer) {
                if ($answer['type'] === 16 && $answer['data'] === $dnsDigest) {
                    return true;
                }
            }
        }
        return false;
    }

    private function cleanupFiles(string $basename): void
    {
        $basePath = $this->getCertificateBasePath($basename);
        $filesList = scandir($basePath);
        if ($filesList !== false) {
            array_walk($filesList, static function (string $file) use ($basePath) {
                if (is_file($basePath . $file)) {
                    unlink($basePath . $file);
                }
            });
        }
    }

    private function getCertificateBasePath(string $basename): string
    {
        return $this->filesPath . DIRECTORY_SEPARATOR . $basename . DIRECTORY_SEPARATOR;
    }

    private function getOrderFilePath(string $basename): string
    {
        return $this->getCertificateBasePath($basename) . File::ORDER;
    }

    private function getPrivateKeyPath(string $basename): string
    {
        return $this->getCertificateBasePath($basename) . File::PRIVATE_KEY;
    }

    private function getPublicKeyPath(string $basename): string
    {
        return $this->getCertificateBasePath($basename) . File::PUBLIC_KEY;
    }
}
