<?php

declare(strict_types=1);

namespace LetsEncrypt\Service;

use LetsEncrypt\Entity\Account;
use LetsEncrypt\Entity\Authorization;
use LetsEncrypt\Entity\Challenge;
use LetsEncrypt\Helper\Base64;
use LetsEncrypt\Http\ConnectorAwareTrait;

class AuthorizationService
{
    use ConnectorAwareTrait;

    public function getAuthorizations(array $urls): array
    {
        $authorizations = [];
        foreach ($urls as $url) {
            $authorizations[] = $this->updateAuthorization($url);
        }
        return $authorizations;
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
                        $dnsDigest = Base64::hashEncode($keyAuthorization);
                        $pendingAuthorizations[] = [
                            'type' => $type,
                            'identifier' => $authorization->identifier['value'],
                            'DNSDigest' => $dnsDigest,
                        ];
                        break;
                }
            }
        }

        return $pendingAuthorizations;
    }

    /**
     * @param Account $account
     * @param string $digest
     * @param Authorization[] $authorizations
     * @param string $identifier
     * @param string $type
     * @return bool
     */
    public function verifyPendingAuthorization(
        Account $account,
        string $digest,
        array $authorizations,
        string $identifier,
        string $type
    ): bool {
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
                                $response = $this->getConnector()->requestWithKIDSigned(
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
                            $dnsDigest = Base64::hashEncode($keyAuthorization);
                            if ($this->verifyDnsChallenge($identifier, $dnsDigest)) {
                                $payload = [
                                    'keyAuthorization' => $keyAuthorization,
                                ];
                                $response = $this->getConnector()->requestWithKIDSigned(
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
        $response = $this->getConnector()->get($domain . '/.well-known/acme-challenge/' . $token);

        return $response->getRawBody() === $key;
    }

    private function verifyDnsChallenge(string $domain, string $dnsDigest): bool
    {
        $query = [
            'type' => 'TXT',
            'name' => '_acme-challenge.' . $domain,
        ];

        $response = $this->getConnector()->get(Challenge::DNS_VERIFY_URI . '?' . http_build_query($query));
        $data = $response->getPayload();

        if ($data['Status'] === 0 && isset($data['Answer'])) {
            foreach ($data['Answer'] as $answer) {
                if ($answer['type'] === 16 && $answer['data'] === $dnsDigest) {
                    return true;
                }
            }
        }
        return false;
    }

    private function updateAuthorization(string $url): Authorization
    {
        $response = $this->getConnector()->get($url);

        return new Authorization($response->getPayload(), $url);
    }
}
