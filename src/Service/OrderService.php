<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019-2020
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Service;

use GuzzleHttp\Exception\TransferException;
use LetsEncrypt\Assertion\Assert;
use LetsEncrypt\Certificate\Bundle;
use LetsEncrypt\Certificate\Certificate;
use LetsEncrypt\Certificate\RevocationReason;
use LetsEncrypt\Entity\Account;
use LetsEncrypt\Entity\Order;
use LetsEncrypt\Enum\KeyType;
use LetsEncrypt\Exception\EnvironmentException;
use LetsEncrypt\Exception\FileIOException;
use LetsEncrypt\Exception\OrderException;
use LetsEncrypt\Helper\FileSystem;
use LetsEncrypt\Helper\KeyGeneratorAwareTrait;
use LetsEncrypt\Http\ConnectorAwareTrait;
use LetsEncrypt\Http\Response;

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
        Assert::directory($filesPath, 'Certificates directory path %s is not a directory');

        $this->authorizationService = $authorizationService;
        $this->filesPath = rtrim($filesPath, DIRECTORY_SEPARATOR);
    }

    /**
     * @throws OrderException
     */
    public function create(Account $account, string $basename, array $subjects, Certificate $certificate): Order
    {
        $directoryName = self::getDomainDirectoryName($basename, $certificate->getKeyType()->getValue());

        $this->processOrderBasePath($directoryName);

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
                $this->connector->getNewOrderUrl(),
                $payload,
                $account->getPrivateKeyPath()
            );
        } catch (TransferException $e) {
            $this->cleanupFiles($directoryName);
            throw new OrderException('Unable to create order');
        }

        $orderUrl = $response->getLocation();

        try {
            FileSystem::writeFileContent(
                $this->getOrderFilePath($directoryName),
                $orderUrl
            );
        } catch (FileIOException $e) {
            throw new OrderException('Unable to store order file');
        }

        $certificate->generate(
            $this->keyGenerator,
            $this->getPrivateKeyPath($directoryName),
            $this->getPublicKeyPath($directoryName)
        );

        return $this->createOrderFromResponse($response, $orderUrl);
    }

    /**
     * @throws OrderException
     */
    public function get(string $basename, array $subjects, KeyType $keyType): Order
    {
        $directoryName = self::getDomainDirectoryName($basename, $keyType->getValue());

        $orderFilePath = $this->getOrderFilePath($directoryName);

        Assert::fileExists($orderFilePath, 'Order file %s does not exist');
        Assert::readable($orderFilePath, 'Order file %s is not readable');

        try {
            $orderUrl = FileSystem::readFileContent($orderFilePath);
        } catch (FileIOException $e) {
            throw new OrderException('Unable to get order url');
        }

        $response = $this->connector->get($orderUrl);

        $order = $this->createOrderFromResponse($response, $orderUrl);

        if ($order->isInvalid()) {
            throw new OrderException('Order has invalid status');
        }
        if (!$order->isIdentifiersEqual($subjects)) {
            throw new OrderException('Order data is invalid - subjects are not equal');
        }

        return $order;
    }

    public function getOrCreate(Account $account, string $basename, array $subjects, Certificate $certificate): Order
    {
        $directoryName = self::getDomainDirectoryName($basename, $certificate->getKeyType()->getValue());

        try {
            $order = $this->get($basename, $subjects, $certificate->getKeyType());
        } catch (\Throwable $e) {
            $this->cleanupFiles($directoryName);
            $order = $this->create($account, $basename, $subjects, $certificate);
        }

        return $order;
    }

    public function getPendingHttpAuthorizations(Account $account, Order $order): array
    {
        return $this->authorizationService->getPendingHttpAuthorizations(
            $order->getPendingAuthorizations(),
            $this->connector->getSigner()->kty($account->getPrivateKeyPath())
        );
    }

    public function getPendingDnsAuthorizations(Account $account, Order $order): array
    {
        return $this->authorizationService->getPendingDnsAuthorizations(
            $order->getPendingAuthorizations(),
            $this->connector->getSigner()->kty($account->getPrivateKeyPath())
        );
    }

    public function verifyPendingHttpAuthorization(Account $account, Order $order, string $identifier): bool
    {
        $authorizations = $order->getAuthorizations();

        return $this->authorizationService->verifyPendingHttpAuthorization($account, $authorizations, $identifier);
    }

    public function verifyPendingDnsAuthorization(Account $account, Order $order, string $identifier): bool
    {
        $authorizations = $order->getAuthorizations();

        return $this->authorizationService->verifyPendingDnsAuthorization($account, $authorizations, $identifier);
    }

    /**
     * @throws OrderException
     */
    public function getCertificate(Account $account, Order $order, string $basename, KeyType $keyType): void
    {
        if (!$order->allAuthorizationsValid()) {
            throw new OrderException('Order authorizations are not valid');
        }

        $directoryName = self::getDomainDirectoryName($basename, $keyType->getValue());

        if ($order->isPending() || $order->isReady()) {
            $order = $this->finalize($account, $order, $basename, $directoryName);
        }

        while ($order->isProcessing()) {
            sleep(5);
            $response = $this->connector->get($order->getUrl());
            $order = $this->createOrderFromResponse($response, $order->getUrl());
        }

        if (!$order->isValid()) {
            throw new OrderException('Order status is invalid');
        }

        $response = $this->connector->signedKIDRequest(
            $account->getUrl(),
            $order->getCertificateRequestUrl(),
            [],
            $account->getPrivateKeyPath()
        );

        $this->extractAndStoreCertificates($directoryName, $response->getRawContent());
    }

    public function revokeCertificate(
        Account $account,
        string $basename,
        KeyType $keyType,
        RevocationReason $reason = null
    ): bool {
        $directoryName = self::getDomainDirectoryName($basename, $keyType->getValue());

        $certificatePrivateKeyPath = $this->getPrivateKeyPath($directoryName);
        Assert::fileExists($certificatePrivateKeyPath);

        $certificatePath = $this->getCertificatePath($directoryName);
        Assert::fileExists($certificatePath);
        Assert::readable($certificatePath);

        if ($reason === null) {
            $reason = RevocationReason::unspecified();
        }

        $certificateContent = FileSystem::readFileContent($certificatePath);

        $pattern = '~-----BEGIN\sCERTIFICATE-----(.*)-----END\sCERTIFICATE-----~s';

        preg_match($pattern, $certificateContent, $matches);
        $encodedCertificate = $this->connector
            ->getSigner()
            ->getBase64Encoder()
            ->encode(base64_decode(trim($matches[1])));

        $payload = [
            'certificate' => $encodedCertificate,
            'reason' => $reason,
        ];

        $response = $this->connector->signedKIDRequest(
            $this->connector->getRevokeCertificateUrl(),
            $account->getUrl(),
            $payload,
            $certificatePrivateKeyPath
        );

        return $response->isStatusOk();
    }

    private function extractAndStoreCertificates(string $directoryName, string $content): void
    {
        $pattern = '~(-----BEGIN\sCERTIFICATE-----[\s\S]+?-----END\sCERTIFICATE-----)~i';

        if (preg_match_all($pattern, $content, $matches) !== false) {
            $certificateContent = $matches[0][0];

            FileSystem::writeFileContent(
                $this->getCertificatePath($directoryName),
                $certificateContent
            );

            $partsCount = count($matches[0]);

            if ($partsCount > 1) {
                $fullChainContent = '';
                for ($i = 1; $i < $partsCount; ++$i) {
                    $fullChainContent .= PHP_EOL . $matches[0][$i];
                }

                FileSystem::writeFileContent(
                    $this->getFullChainCertificatePath($directoryName),
                    $fullChainContent
                );
            }
        }
    }

    private function finalize(Account $account, Order $order, string $basename, string $directoryName): Order
    {
        $csr = $this->keyGenerator->csr(
            $basename,
            $order->getIdentifiers(),
            $this->getPrivateKeyPath($directoryName)
        );

        $payload = [
            'csr' => $this->connector
                ->getSigner()
                ->getBase64Encoder()
                ->encode(base64_decode($csr)),
        ];

        $response = $this->connector->signedKIDRequest(
            $account->getUrl(),
            $order->getFinalizeUrl(),
            $payload,
            $account->getPrivateKeyPath()
        );

        return $this->createOrderFromResponse($response, $order->getUrl());
    }

    private function createOrderFromResponse(Response $response, string $url): Order
    {
        $data = $response->getDecodedContent();
        // fetch authorizations data
        $data['authorizations'] = $this->authorizationService->getAuthorizations($data['authorizations']);

        return new Order($data, $url);
    }

    /**
     * @throws EnvironmentException
     */
    private function processOrderBasePath(string $directoryName): void
    {
        $basePath = $this->getCertificateBasePath($directoryName);

        // try to create certificate directory if it's not exist
        if (!is_dir($basePath) && !mkdir($basePath, 0755)) {
            throw new EnvironmentException('Unable to create certificate directory "' . $basePath . '"');
        }

        Assert::directory($basePath, 'Certificate directory path %s is not a directory');
        Assert::writable($basePath, 'Certificates directory path %s is not writable');
    }

    private function cleanupFiles(string $directoryName): void
    {
        $basePath = $this->getCertificateBasePath($directoryName);

        if (!is_dir($basePath)) {
            return;
        }

        $filesList = scandir($basePath);
        if ($filesList !== false) {
            array_walk($filesList, static function (string $file) use ($basePath) {
                if (is_file($basePath . $file)) {
                    unlink($basePath . $file);
                }
            });
        }
    }

    public static function getDomainDirectoryName(string $basename, string $type): string
    {
        return $basename . ':' . $type;
    }

    private function getCertificateBasePath(string $directoryName): string
    {
        return $this->filesPath . DIRECTORY_SEPARATOR . $directoryName . DIRECTORY_SEPARATOR;
    }

    public function getOrderFilePath(string $directoryName): string
    {
        return $this->getCertificateBasePath($directoryName) . Bundle::ORDER;
    }

    public function getPrivateKeyPath(string $directoryName): string
    {
        return $this->getCertificateBasePath($directoryName) . Bundle::PRIVATE_KEY;
    }

    public function getPublicKeyPath(string $directoryName): string
    {
        return $this->getCertificateBasePath($directoryName) . Bundle::PUBLIC_KEY;
    }

    public function getCertificatePath(string $directoryName): string
    {
        return $this->getCertificateBasePath($directoryName) . Bundle::CERTIFICATE;
    }

    public function getFullChainCertificatePath(string $directoryName): string
    {
        return $this->getCertificateBasePath($directoryName) . Bundle::FULL_CHAIN_CERTIFICATE;
    }
}
