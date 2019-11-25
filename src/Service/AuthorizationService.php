<?php

declare(strict_types=1);

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
     * @param array $urls
     * @return Authorization[]
     */
    public function getAuthorizations(array $urls): array
    {
        return array_map(function (string $url) {
            return $this->updateAuthorization($url);
        }, $urls);
    }

    /**
     * @param string $digest
     * @param Authorization[] $authorizations
     * @param string $type
     * @return array
     */
    public function getPendingAuthorizations(string $digest, array $authorizations, string $type): array
    {
        $pendingAuthorizations = [];

        foreach ($authorizations as $authorization) {
            $challenge = $authorization->getChallenge($type);
            if ($challenge->isPending()) {
                $keyAuthorization = $challenge->token . '.' . $digest;
                switch (true) {
                    case $challenge->isHttp():
                        $pendingAuthorizations[] = [
                            'type' => $type,
                            'identifier' => $authorization->identifier['value'],
                            'filename' => $challenge->token,
                            'content' => $keyAuthorization,
                        ];
                        break;
                    case $challenge->isDns():
                        $dnsDigest = $this->connector->getSigner()->getBase64Encoder()->hashEncode($keyAuthorization);
                        $pendingAuthorizations[] = [
                            'type' => $type,
                            'identifier' => $authorization->identifier['value'],
                            'dnsDigest' => $dnsDigest,
                        ];
                        break;
                }
            }
        }

        return $pendingAuthorizations;
    }

    /**
     * @param Account $account
     * @param Authorization[] $authorizations
     * @param string $identifier
     * @param string $type
     * @return bool
     */
    public function verifyPendingAuthorization(
        Account $account,
        array $authorizations,
        string $identifier,
        string $type
    ): bool {
        $digest = $this->connector->getSigner()->kty($account->getPrivateKeyPath());

        foreach ($authorizations as $authorization) {
            if ($authorization->identifier['value'] === $identifier && $authorization->isPending()) {
                $challenge = $authorization->getChallenge($type);
                if ($challenge->isPending()) {
                    $keyAuthorization = $challenge->token . '.' . $digest;

                    switch (true) {
                        case $challenge->isHttp():
                            if ($this->verifyHttpChallenge($identifier, $challenge->token, $keyAuthorization)) {
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
                            break;
                        case $challenge->isDns():
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
                            break;
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
        $dnsDigest = $this->connector->getSigner()->getBase64Encoder()->hashEncode($keyAuthorization);

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
