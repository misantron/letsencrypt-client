<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Service;

use LetsEncrypt\Entity\Account;
use LetsEncrypt\Entity\Authorization;
use LetsEncrypt\Http\ConnectorAwareTrait;
use LetsEncrypt\Http\GooglePublicDNS;

class AuthorizationService
{
    use ConnectorAwareTrait;

    /**
     * @var GooglePublicDNS
     */
    private $googlePublicDNS;

    public function __construct()
    {
        $this->googlePublicDNS = new GooglePublicDNS();
    }

    /**
     * @return Authorization[]
     */
    public function getAuthorizations(array $urls): array
    {
        return array_map(function (string $url) {
            return $this->updateAuthorization($url);
        }, $urls);
    }

    /**
     * @param Authorization[] $authorizations
     */
    public function getPendingHttpAuthorizations(array $authorizations, string $digest): array
    {
        $pendingAuthorizations = [];

        foreach ($authorizations as $authorization) {
            $challenge = $authorization->getHttpChallenge();
            if ($challenge->isPending()) {
                $keyAuthorization = $challenge->getToken() . '.' . $digest;
                $pendingAuthorizations[] = [
                    'identifier' => $authorization->getIdentifierValue(),
                    'filename' => $challenge->getToken(),
                    'content' => $keyAuthorization,
                ];
            }
        }

        return $pendingAuthorizations;
    }

    /**
     * @param Authorization[] $authorizations
     */
    public function getPendingDnsAuthorizations(array $authorizations, string $digest): array
    {
        $pendingAuthorizations = [];

        foreach ($authorizations as $authorization) {
            $challenge = $authorization->getDnsChallenge();
            if ($challenge->isPending()) {
                $keyAuthorization = $challenge->getToken() . '.' . $digest;
                $pendingAuthorizations[] = [
                    'identifier' => $authorization->getIdentifierValue(),
                    'dnsDigest' => $this->connector
                        ->getSigner()
                        ->getBase64Encoder()
                        ->hashEncode($keyAuthorization),
                ];
            }
        }

        return $pendingAuthorizations;
    }

    /**
     * @param Authorization[] $authorizations
     */
    public function verifyPendingHttpAuthorization(Account $account, array $authorizations, string $identifier): bool
    {
        $digest = $this->connector->getSigner()->kty($account->getPrivateKeyPath());

        foreach ($authorizations as $authorization) {
            if ($authorization->isPending() && $authorization->isIdentifierValueEqual($identifier)) {
                $challenge = $authorization->getHttpChallenge();
                if ($challenge->isPending()) {
                    $keyAuthorization = $challenge->getToken() . '.' . $digest;

                    if ($this->verifyHttpChallenge($identifier, $challenge->getToken(), $keyAuthorization)) {
                        $payload = [
                            'keyAuthorization' => $keyAuthorization,
                        ];
                        $response = $this->connector->signedKIDRequest(
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
                }
            }
        }

        return false;
    }

    /**
     * @param Authorization[] $authorizations
     */
    public function verifyPendingDnsAuthorization(Account $account, array $authorizations, string $identifier): bool
    {
        $digest = $this->connector->getSigner()->kty($account->getPrivateKeyPath());

        foreach ($authorizations as $authorization) {
            if ($authorization->isPending() && $authorization->isIdentifierValueEqual($identifier)) {
                $challenge = $authorization->getDnsChallenge();
                if ($challenge->isPending()) {
                    $keyAuthorization = $challenge->getToken() . '.' . $digest;

                    if ($this->verifyDnsChallenge($identifier, $keyAuthorization)) {
                        $payload = [
                            'keyAuthorization' => $keyAuthorization,
                        ];
                        $response = $this->connector->signedKIDRequest(
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
                }
            }
        }

        return false;
    }

    private function verifyHttpChallenge(string $domain, string $token, string $key): bool
    {
        $response = $this->connector->get($domain . '/.well-known/acme-challenge/' . $token);

        return $response->getRawContent() === $key;
    }

    private function verifyDnsChallenge(string $domain, string $keyAuthorization): bool
    {
        $dnsDigest = $this->connector
            ->getSigner()
            ->getBase64Encoder()
            ->hashEncode($keyAuthorization);

        return $this->googlePublicDNS
            ->setConnector($this->connector)
            ->verify($domain, $dnsDigest);
    }

    private function updateAuthorization(string $url): Authorization
    {
        $response = $this->connector->get($url);

        return new Authorization($response->getDecodedContent(), $url);
    }
}
