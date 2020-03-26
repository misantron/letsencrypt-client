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

use LetsEncrypt\Assertion\Assert;
use LetsEncrypt\Certificate\Bundle;
use LetsEncrypt\Entity\Account;
use LetsEncrypt\Exception\AccountException;
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

        $this->keysPath = rtrim($keysPath, DIRECTORY_SEPARATOR);
    }

    public function get(): Account
    {
        Assert::fileExists($this->getPrivateKeyPath(), 'Private key %s does not exist');
        Assert::fileExists($this->getPublicKeyPath(), 'Public key %s does not exist');

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

    public function keyRollover(): void
    {
        $account = $this->getAccount();

        // generate new key pair
        $this->keyGenerator->rsa($this->getTmpPrivateKeyPath(), $this->getTmpPublicKeyPath());

        $payload = [
            'account' => $account->getUrl(),
            'oldKey' => $this->connector->getSigner()->jwk($this->getPrivateKeyPath()),
        ];

        $signedPayload = $this->connector->signedJWS(
            $this->connector->getAccountKeyChangeUrl(),
            $payload,
            $this->getTmpPrivateKeyPath()
        );

        $response = $this->connector->signedKIDRequest(
            $account->getUrl(),
            $this->connector->getAccountKeyChangeUrl(),
            $signedPayload,
            $this->getPrivateKeyPath()
        );

        if (!$response->isStatusOk()) {
            throw new AccountException('Account key rollover failed');
        }

        unlink($this->getPrivateKeyPath());
        unlink($this->getPublicKeyPath());

        rename($this->getTmpPrivateKeyPath(), $this->getPrivateKeyPath());
        rename($this->getTmpPublicKeyPath(), $this->getPublicKeyPath());
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
            $this->connector->getNewAccountUrl(),
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
            $this->connector->getNewAccountUrl(),
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

    private function getTmpPrivateKeyPath(): string
    {
        return $this->getPrivateKeyPath() . '.new';
    }

    private function getPublicKeyPath(): string
    {
        return $this->keysPath . DIRECTORY_SEPARATOR . Bundle::PUBLIC_KEY;
    }

    private function getTmpPublicKeyPath(): string
    {
        return $this->getPublicKeyPath() . '.new';
    }
}
