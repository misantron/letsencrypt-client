<?php

declare(strict_types=1);

namespace LetsEncrypt\Service;

use LetsEncrypt\Certificate\File;
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

    public function update(Account $account, array $emails): Account
    {
        $contact = array_map(static function (string $email) {
            return strpos($email, 'mailto') === false ? 'mailto:' . $email : $email;
        }, $emails);

        $payload = ['contact' => $contact];

        $response = $this->connector->signedKIDRequest(
            $account->getUrl(),
            $account->getUrl(),
            $payload,
            $this->getPrivateKeyPath()
        );

        $state = clone $account;

        return new Account(
            $response->getDecodedContent(),
            $state->getUrl(),
            $state->getPrivateKeyPath()
        );
    }

    public function deactivate(Account $account): Account
    {
        $payload = ['status' => 'deactivated'];

        $response = $this->connector->signedKIDRequest(
            $account->getUrl(),
            $account->getUrl(),
            $payload,
            $this->getPrivateKeyPath()
        );

        $state = clone $account;

        return new Account(
            $response->getDecodedContent(),
            $state->getUrl(),
            $state->getPrivateKeyPath()
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

        $response = $this->connector->signedJWKRequest(
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

        $response = $this->connector->signedJWKRequest(
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
        return $this->keysPath . DIRECTORY_SEPARATOR . File::PRIVATE_KEY;
    }

    private function getPublicKeyPath(): string
    {
        return $this->keysPath . DIRECTORY_SEPARATOR . File::PUBLIC_KEY;
    }
}
