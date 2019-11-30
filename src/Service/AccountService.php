<?php

declare(strict_types=1);

namespace LetsEncrypt\Service;

use LetsEncrypt\Assertion\Assert;
use LetsEncrypt\Certificate\Bundle;
use LetsEncrypt\Entity\Account;
use LetsEncrypt\Helper\KeyGeneratorAwareTrait;
use LetsEncrypt\Http\ConnectorAwareTrait;
use LetsEncrypt\Http\Response;

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
        Assert::directory($keysPath, 'Account keys directory path %s is not a directory');

        $this->keysPath = $keysPath;
    }

    public function get(): Account
    {
        Assert::fileExists($this->getPrivateKeyPath());
        Assert::fileExists($this->getPublicKeyPath());

        return $this->getAccount();
    }

    public function create(array $emails): Account
    {
        $this->keyGenerator->rsa($this->getPrivateKeyPath(), $this->getPublicKeyPath());

        return $this->createAccount($emails);
    }

    public function update(array $emails): Account
    {
        $account = $this->getAccount();

        $payload = [
            'contact' => $this->contactFromEmails($emails),
        ];

        $response = $this->connector->signedKIDRequest(
            $account->getUrl(),
            $account->getUrl(),
            $payload,
            $this->getPrivateKeyPath()
        );

        return $this->createAccountFromResponse($response);
    }

    public function deactivate(): Account
    {
        $account = $this->getAccount();

        $payload = [
            'status' => 'deactivated',
        ];

        $response = $this->connector->signedKIDRequest(
            $account->getUrl(),
            $account->getUrl(),
            $payload,
            $this->getPrivateKeyPath()
        );

        return $this->createAccountFromResponse($response);
    }

    private function createAccount(array $emails): Account
    {
        $payload = [
            'contact' => $this->contactFromEmails($emails),
            'termsOfServiceAgreed' => true,
        ];

        $response = $this->connector->signedJWSRequest(
            $this->connector->getNewAccountEndpoint(),
            $payload,
            $this->getPrivateKeyPath()
        );

        return $this->createAccountFromResponse($response);
    }

    private function getAccount(): Account
    {
        $payload = [
            'onlyReturnExisting' => true,
        ];

        $response = $this->connector->signedJWSRequest(
            $this->connector->getNewAccountEndpoint(),
            $payload,
            $this->getPrivateKeyPath()
        );

        return $this->createAccountFromResponse($response);
    }

    private function contactFromEmails(array $emails): array
    {
        return array_map(static function (string $email) {
            return strpos($email, 'mailto') === false ? 'mailto:' . $email : $email;
        }, $emails);
    }

    private function createAccountFromResponse(Response $response): Account
    {
        return new Account(
            $response->getDecodedContent(),
            $response->getLocation(),
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
