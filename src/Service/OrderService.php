<?php

declare(strict_types=1);

namespace LetsEncrypt\Service;

use GuzzleHttp\Exception\TransferException;
use LetsEncrypt\Assertion\Assert;
use LetsEncrypt\Certificate\Certificate;
use LetsEncrypt\Certificate\Bundle;
use LetsEncrypt\Certificate\RevocationReason;
use LetsEncrypt\Entity\Account;
use LetsEncrypt\Entity\Order;
use LetsEncrypt\Exception\EnvironmentException;
use LetsEncrypt\Exception\FileIOException;
use LetsEncrypt\Exception\OrderException;
use LetsEncrypt\Helper\KeyGeneratorAwareTrait;
use LetsEncrypt\Helper\FileSystem;
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
        $this->filesPath = $filesPath;
    }

    /**
     * @param Account $account
     * @param string $basename
     * @param array $subjects
     * @param Certificate $certificate
     * @return Order
     *
     * @throws EnvironmentException
     * @throws OrderException
     */
    public function create(Account $account, string $basename, array $subjects, Certificate $certificate): Order
    {
        $certificateBasePath = $this->getCertificateBasePath($basename);

        if (!mkdir($certificateBasePath, 0755)) {
            throw new EnvironmentException('Unable to create certificate directory "' . $certificateBasePath . '"');
        }

        Assert::directory($certificateBasePath, 'Certificate directory path %s is not a directory');
        Assert::writable($certificateBasePath, 'Certificates directory path %s is not writable');

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
            throw new OrderException('Unable to create order');
        }

        $orderUrl = $response->getLocation();

        try {
            FileSystem::writeFileContent(
                $this->getOrderFilePath($basename),
                $orderUrl
            );
        } catch (FileIOException $e) {
            throw new OrderException('Unable to store order file');
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

    /**
     * @param string $basename
     * @param array $subjects
     * @return Order
     *
     * @throws OrderException
     */
    public function get(string $basename, array $subjects): Order
    {
        $orderFilePath = $this->getOrderFilePath($basename);

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
        try {
            $order = $this->get($basename, $subjects);
        } catch (\Throwable $e) {
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

    /**
     * @param Account $account
     * @param Order $order
     * @param string $basename
     *
     * @throws OrderException
     */
    public function getCertificate(Account $account, Order $order, string $basename): void
    {
        if ($order->isPending() || $order->isReady()) {
            $order = $this->finalize($account, $order, $basename);
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

        $certificates = $this->extractCertificates($response->getRawContent());

        if (isset($certificates[Bundle::CERTIFICATE])) {
            FileSystem::writeFileContent(
                $this->getCertificatePath($basename),
                $certificates[Bundle::CERTIFICATE]
            );
        }
        if (isset($certificates[Bundle::FULL_CHAIN_CERTIFICATE])) {
            FileSystem::writeFileContent(
                $this->getFullChainCertificatePath($basename),
                $certificates[Bundle::FULL_CHAIN_CERTIFICATE]
            );
        }
    }

    /**
     * @param Account $account
     * @param string $basename
     * @param RevocationReason|null $reason
     * @return bool
     */
    public function revokeCertificate(Account $account, string $basename, RevocationReason $reason = null): bool
    {
        $certificatePrivateKeyPath = $this->getPrivateKeyPath($basename);
        Assert::fileExists($certificatePrivateKeyPath);

        $certificatePath = $this->getCertificatePath($basename);
        Assert::fileExists($certificatePath);
        Assert::readable($certificatePath);

        if ($reason === null) {
            $reason = RevocationReason::unspecified();
        }

        $certificateContent = FileSystem::readFileContent($certificatePath);

        preg_match('~-----BEGIN\sCERTIFICATE-----(.*)-----END\sCERTIFICATE-----~s', $certificateContent, $matches);
        $encodedCertificate = $this->connector
            ->getSigner()
            ->getBase64Encoder()
            ->encode(base64_decode(trim($matches[1])));

        $payload = [
            'certificate' => $encodedCertificate,
            'reason' => $reason,
        ];

        $response = $this->connector->signedKIDRequest(
            $this->connector->getRevokeCertificateEndpoint(),
            $account->getUrl(),
            $payload,
            $certificatePrivateKeyPath
        );

        return $response->isStatusOk();
    }

    /**
     * @param string $content
     * @return array
     */
    private function extractCertificates(string $content): array
    {
        $pattern = '~(-----BEGIN\sCERTIFICATE-----[\s\S]+?-----END\sCERTIFICATE-----)~i';

        $files = [];

        if (preg_match_all($pattern, $content, $matches) !== false) {
            $files[Bundle::CERTIFICATE] = $matches[0][0];

            $partsCount = count($matches[0]);

            if ($partsCount > 1) {
                $fullChainContent = '';
                for ($i = 1; $i < $partsCount; ++$i) {
                    $fullChainContent .= PHP_EOL . $matches[0][$i];
                }

                $files[Bundle::FULL_CHAIN_CERTIFICATE] = $fullChainContent;
            }
        }

        return $files;
    }

    /**
     * @param Account $account
     * @param Order $order
     * @param string $basename
     * @return Order
     */
    private function finalize(Account $account, Order $order, string $basename): Order
    {
        $csr = $this->keyGenerator->csr(
            $basename,
            $order->getIdentifiers(),
            $this->getPrivateKeyPath($basename)
        );

        $csrEncoded = $this->connector
            ->getSigner()
            ->getBase64Encoder()
            ->encode(base64_decode($csr));

        $payload = [
            'csr' => $csrEncoded,
        ];

        $response = $this->connector->signedKIDRequest(
            $account->getUrl(),
            $order->getFinalizeUrl(),
            $payload,
            $account->getPrivateKeyPath()
        );

        return $this->createOrderFromResponse($response, $order->getUrl());
    }

    /**
     * @param Response $response
     * @param string $url
     * @return Order
     */
    private function createOrderFromResponse(Response $response, string $url): Order
    {
        $data = $response->getDecodedContent();
        // fetch authorizations data
        $data['authorizations'] = $this->authorizationService->getAuthorizations($data['authorizations']);

        return new Order($data, $url);
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

    public function getOrderFilePath(string $basename): string
    {
        return $this->getCertificateBasePath($basename) . Bundle::ORDER;
    }

    public function getPrivateKeyPath(string $basename): string
    {
        return $this->getCertificateBasePath($basename) . Bundle::PRIVATE_KEY;
    }

    public function getPublicKeyPath(string $basename): string
    {
        return $this->getCertificateBasePath($basename) . Bundle::PUBLIC_KEY;
    }

    public function getCertificatePath(string $basename): string
    {
        return $this->getCertificateBasePath($basename) . Bundle::CERTIFICATE;
    }

    public function getFullChainCertificatePath(string $basename): string
    {
        return $this->getCertificateBasePath($basename) . Bundle::FULL_CHAIN_CERTIFICATE;
    }
}
