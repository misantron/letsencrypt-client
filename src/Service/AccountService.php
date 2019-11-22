<?php

declare(strict_types=1);

namespace LetsEncrypt\Service;

use LetsEncrypt\Assert\Assert;
use LetsEncrypt\Certificate\File;
use LetsEncrypt\Entity\Account;
use LetsEncrypt\Helper\Key;
use LetsEncrypt\Http\Connector;

class AccountService extends AbstractService
{
    /**
     * @var string
     */
    private $keysPath;

    public function __construct(Connector $connector, string $keysPath)
    {
        parent::__construct($connector);

        Assert::directoryExists($keysPath);

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
        Key::rsa($this->getPrivateKeyPath(), $this->getPublicKeyPath());

        $url = $this->createAccount($emails);

        return $this->getProfile($url);
    }

    public function update(Account $account, array $emails): Account
    {
        $contact = array_map(static function (string $email) {
            return strpos($email, 'mailto') === false ? 'mailto:' . $email : $email;
        }, $emails);

        $payload = ['contact' => $contact];

        $response = $this->getConnector()->requestWithKIDSigned(
            $account->getUrl(),
            $account->getUrl(),
            $payload,
            $this->getPrivateKeyPath()
        );

        return new Account($response->getPayload());
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

        $response = $this->getConnector()->requestWithJWKSigned(
            $this->getConnector()->getEndpoint()->newAccount,
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

        $response = $this->getConnector()->requestWithJWKSigned(
            $this->getConnector()->getEndpoint()->newAccount,
            $payload,
            $this->getPrivateKeyPath()
        );

        return $response->getLocation();
    }

    private function getProfile(string $url): Account
    {
        $payload = ['' => ''];

        $response = $this->getConnector()->requestWithKIDSigned(
            $url,
            $url,
            $payload,
            $this->getPrivateKeyPath()
        );

        $account = new Account($response->getPayload());
        $account->setUrl($url);

        return $account;
    }

    private function getPrivateKeyPath(): string
    {
        return $this->keysPath . DIRECTORY_SEPARATOR . File::PRIVATE_KEY;
    }

    private function getPublicKeyPath(): string
    {
        return $this->keysPath . DIRECTORY_SEPARATOR . File::PUBLIC_KEY;
    }
}
