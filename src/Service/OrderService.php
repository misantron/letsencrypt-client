<?php

declare(strict_types=1);

namespace LetsEncrypt\Service;

use GuzzleHttp\Exception\TransferException;
use LetsEncrypt\Certificate\Certificate;
use LetsEncrypt\Certificate\File;
use LetsEncrypt\Entity\Account;
use LetsEncrypt\Entity\Order;
use LetsEncrypt\Helper\KeyGeneratorAwareTrait;
use LetsEncrypt\Helper\FileSystem;
use LetsEncrypt\Http\ConnectorAwareTrait;
use LetsEncrypt\Http\Response;
use Webmozart\Assert\Assert;

class OrderService
{
    use ConnectorAwareTrait;
    use KeyGeneratorAwareTrait;

    /**
     * @var AuthorizationService
     */
    private $authorizationService;

    /**
     * @var string
     */
    private $filesPath;

    public function __construct(AuthorizationService $authorizationService, string $filesPath)
    {
        Assert::directory($filesPath);

        $this->authorizationService = $authorizationService;
        $this->filesPath = $filesPath;
    }

    public function create(Account $account, string $basename, array $subjects, Certificate $certificate): Order
    {
        $certificateBasePath = $this->getCertificateBasePath($basename);

        if (!mkdir($certificateBasePath, 0755)) {
            throw new \RuntimeException('Unable to create certificate directory: ' . $certificateBasePath);
        }

        Assert::directory($certificateBasePath);
        Assert::writable($certificateBasePath);

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

        try {
            $response = $this->connector->signedKIDRequest(
                $account->getUrl(),
                $this->connector->getNewOrderEndpoint(),
                $payload,
                $account->getPrivateKeyPath()
            );
        } catch (TransferException $e) {
            $this->cleanupFiles($basename);
            throw new \RuntimeException('Unable to create order');
        }

        $orderUrl = $response->getLocation();

        try {
            FileSystem::writeFileContent(
                $this->getOrderFilePath($basename),
                $orderUrl
            );
        } catch (\Throwable $e) {

        }

        if ($certificate->getKey()->isRSA()) {
            $this->keyGenerator->rsa(
                $this->getPrivateKeyPath($basename),
                $this->getPublicKeyPath($basename),
                $certificate->getKey()->getLength()
            );
        } elseif ($certificate->getKey()->isEC()) {
            $this->keyGenerator->ec(
                $this->getPrivateKeyPath($basename),
                $this->getPublicKeyPath($basename),
                $certificate->getKey()->getAlgorithm()
            );
        }

        return $this->createOrderFromResponse($response, $orderUrl);
    }

    public function get(string $basename, array $subjects): Order
    {
        $orderFilePath = $this->getOrderFilePath($basename);

        Assert::fileExists($orderFilePath);
        Assert::readable($orderFilePath);

        try {
            $orderUrl = FileSystem::readFileContent($orderFilePath);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Unable to get order url');
        }

        $response = $this->connector->get($orderUrl);

        $order = $this->createOrderFromResponse($response, $orderUrl);

        if ($order->isInvalid()) {
            throw new \RuntimeException('Order has invalid status');
        }
        if (!$order->isIdentifiersEqual($subjects)) {
            throw new \RuntimeException('Order data is invalid - subjects are not equal');
        }

        return $order;
    }

    public function getOrCreate(Account $account, string $basename, array $subjects, Certificate $certificate): Order
    {
        try {
            $order = $this->get($basename, $subjects);
        } catch (\RuntimeException $e) {
            $this->cleanupFiles($basename);
            $order = $this->create($account, $basename, $subjects, $certificate);
        }

        return $order;
    }

    public function getPendingAuthorizations(Account $account, Order $order, string $type): array
    {
        $digest = $this->connector->getSigner()->kty($account->getPrivateKeyPath());
        $authorizations = $order->getPendingAuthorizations();

        return $this->authorizationService->getPendingAuthorizations($digest, $authorizations, $type);
    }

    public function verifyPendingAuthorization(Account $account, Order $order, string $identifier, string $type): bool
    {
        $authorizations = $order->getAuthorizations();

        return $this->authorizationService->verifyPendingAuthorization($account, $authorizations, $identifier, $type);
    }

    public function getCertificate(Account $account, Order $order, string $basename, string $csr = ''): void
    {
        if ($order->isPending() || $order->isReady()) {
            $order = $this->finalize($account, $order, $csr);
        }

        while ($order->isProcessing()) {
            sleep(5);
            $response = $this->connector->get($order->getUrl());
            $order = $this->createOrderFromResponse($response, $order->getUrl());
        }

        if (!$order->isValid()) {
            throw new \RuntimeException('Order status is invalid');
        }

        $response = $this->connector->signedKIDRequest(
            $account->getUrl(),
            $order->certificate,
            [],
            $account->getPrivateKeyPath()
        );

        $certificates = $this->extractCertificates($response->getRawContent());

        if (isset($certificates[File::CERTIFICATE])) {
            FileSystem::writeFileContent(
                $this->getCertificatePath($basename),
                $certificates[File::CERTIFICATE]
            );
        }
        if (isset($certificates[File::FULL_CHAIN_CERTIFICATE])) {
            FileSystem::writeFileContent(
                $this->getFullChainCertificatePath($basename),
                $certificates[File::FULL_CHAIN_CERTIFICATE]
            );
        }
    }

    private function extractCertificates(string $content): array
    {
        $pattern = '~(-----BEGIN\sCERTIFICATE-----[\s\S]+?-----END\sCERTIFICATE-----)~i';

        $files = [];

        if (preg_match_all($pattern, $content, $matches) !== false) {
            $files[File::CERTIFICATE] = $matches[0][0];

            $partsCount = count($matches[0]);

            if ($partsCount > 1) {
                $fullChainContent = '';
                for ($i = 1; $i < $partsCount; ++$i)  {
                    $fullChainContent .= PHP_EOL . $matches[0][$i];
                }

                $files[File::FULL_CHAIN_CERTIFICATE] = $fullChainContent;
            }
        }

        return $files;
    }

    private function finalize(Account $account, Order $order, string $csr = ''): Order
    {
        $payload = [
            'csr' => $this->connector->getSigner()->getBase64Encoder()->encode(base64_decode($csr)),
        ];

        $response = $this->connector->signedKIDRequest(
            $account->getUrl(),
            $order->finalize,
            $payload,
            $account->getPrivateKeyPath()
        );

        return $this->createOrderFromResponse($response, $order->getUrl());
    }

    private function createOrderFromResponse(Response $response, string $url): Order
    {
        $data = $response->getDecodedContent();
        $authorizationsData = $this->authorizationService->getAuthorizations($data['authorizations']);

        return new Order($data, $authorizationsData, $url);
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

    private function getCertificatePath(string $basename): string
    {
        return $this->getCertificateBasePath($basename) . File::CERTIFICATE;
    }

    private function getFullChainCertificatePath(string $basename): string
    {
        return $this->getCertificateBasePath($basename) . File::FULL_CHAIN_CERTIFICATE;
    }
}
