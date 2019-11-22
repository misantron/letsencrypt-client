<?php

declare(strict_types=1);

namespace LetsEncrypt\Service;

use GuzzleHttp\Exception\TransferException;
use LetsEncrypt\Assert\Assert;
use LetsEncrypt\Certificate\Certificate;
use LetsEncrypt\Certificate\File;
use LetsEncrypt\Entity\Account;
use LetsEncrypt\Entity\Order;
use LetsEncrypt\Helper\Base64;
use LetsEncrypt\Helper\Key;
use LetsEncrypt\Helper\Signer;
use LetsEncrypt\Http\ConnectorAwareTrait;

class OrderService
{
    use ConnectorAwareTrait;

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
        Assert::directoryExists($filesPath);

        $this->authorizationService = $authorizationService;
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

        try {
            $response = $this->getConnector()->requestWithKIDSigned(
                $account->getUrl(),
                $this->getConnector()->getNewOrderEndpoint(),
                $payload,
                $account->getPrivateKeyPath()
            );
        } catch (TransferException $e) {
            $this->cleanupFiles($basename);
            throw new \RuntimeException('Unable to create order');
        }

        file_put_contents($this->getOrderFilePath($basename), $response->getLocation());

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

        $data = $response->getPayload();

        return new Order(
            $data,
            $this->authorizationService->getAuthorizations($data['authorizations'])
        );
    }

    public function get(string $basename, array $subjects): Order
    {
        $orderFilePath = $this->getOrderFilePath($basename);
        Assert::fileExists($orderFilePath);

        $orderUrl = file_get_contents($orderFilePath);

        $data = $this->getConnector()->get($orderUrl)->getPayload();

        $order = new Order(
            $data,
            $this->authorizationService->getAuthorizations($data['authorizations'])
        );

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
        return $this->authorizationService->getPendingAuthorizations(
            Signer::kty($account->getPrivateKeyPath()),
            $order->getPendingAuthorizations(),
            $type
        );
    }

    public function verifyPendingAuthorization(string $identifier, string $type, Account $account, Order $order): bool
    {
        return $this->authorizationService->verifyPendingAuthorization(
            $account,
            Signer::kty($account->getPrivateKeyPath()),
            $order->getAuthorizations(),
            $identifier,
            $type
        );
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

        $data = $response->getPayload();

        return new Order(
            $data,
            $this->authorizationService->getAuthorizations($data['authorizations'])
        );
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
