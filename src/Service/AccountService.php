<?php

declare(strict_types=1);

namespace LetsEncrypt\Service;

use LetsEncrypt\Certificate\Bundle;
use LetsEncrypt\Entity\Account;
use LetsEncrypt\Helper\KeyGeneratorAwareTrait;
use LetsEncrypt\Http\ConnectorAwareTrait;
use Webmozart\Assert\Assert;

class AccountService
{
    use ConnectorAwareTrait;
    use KeyGeneratorAwareTrait;

    /**
     * @var string
     */
    private $keysPath;

    public function __construct(string $keysPath)
    {
        Assert::directory($keysPath);

        $this->keysPath = $keysPath;
    }

    public function get(): Account
    {
        Assert::fileExists($this->getPrivateKeyPath());
        Assert::fileExists($this->getPublicKeyPath());

        $url = $this->lookup();

        return $this->getProfile($url);
    }

    public function create(array $emails): Account
    {
        $this->keyGenerator->rsa($this->getPrivateKeyPath(), $this->getPublicKeyPath());

        $url = $this->createAccount($emails);

        return $this->getProfile($url);
    }

    public function update(array $emails): Account
    {
        $accountUrl = $this->lookup();

        $contact = array_map(static function (string $email) {
            return strpos($email, 'mailto') === false ? 'mailto:' . $email : $email;
        }, $emails);

        $payload = ['contact' => $contact];

        $response = $this->connector->signedKIDRequest(
            $accountUrl,
            $accountUrl,
            $payload,
            $this->getPrivateKeyPath()
        );

        return new Account(
            $response->getDecodedContent(),
            $accountUrl,
            $this->getPrivateKeyPath()
        );
    }

    public function deactivate(): Account
    {
        $accountUrl = $this->lookup();

        $payload = ['status' => 'deactivated'];

        $response = $this->connector->signedKIDRequest(
            $accountUrl,
            $accountUrl,
            $payload,
            $this->getPrivateKeyPath()
        );

        return new Account(
            $response->getDecodedContent(),
            $accountUrl,
            $this->getPrivateKeyPath()
        );
    }

    private function createAccount(array $emails): string
    {
        $contact = array_map(static function (string $email) {
            return strpos($email, 'mailto') === false ? 'mailto:' . $email : $email;
        }, $emails);

        $payload = [
            'contact' => $contact,
            'termsOfServiceAgreed' => true,
        ];

        $response = $this->connector->signedJWSRequest(
            $this->connector->getNewAccountEndpoint(),
            $payload,
            $this->getPrivateKeyPath()
        );

        return $response->getLocation();
    }

    private function lookup(): string
    {
        $payload = [
            'onlyReturnExisting' => true,
        ];

        $response = $this->connector->signedJWSRequest(
            $this->connector->getNewAccountEndpoint(),
            $payload,
            $this->getPrivateKeyPath()
        );

        return $response->getLocation();
    }

    private function getProfile(string $url): Account
    {
        $payload = ['' => ''];

        $response = $this->connector->signedKIDRequest(
            $url,
            $url,
            $payload,
            $this->getPrivateKeyPath()
        );

        return new Account(
            $response->getDecodedContent(),
            $url,
            $this->getPrivateKeyPath()
        );
    }

    private function getPrivateKeyPath(): string
    {
        return $this->keysPath . DIRECTORY_SEPARATOR . Bundle::PRIVATE_KEY;
    }

    private function getPublicKeyPath(): string
    {
        return $this->keysPath . DIRECTORY_SEPARATOR . Bundle::PUBLIC_KEY;
    }
}
