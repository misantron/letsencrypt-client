<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019-2020
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Http;

/**
 * Class GooglePublicDNS.
 *
 * @see https://developers.google.com/speed/public-dns/docs/doh/json
 */
final class GooglePublicDNS implements DnsCheckerInterface
{
    use ConnectorAwareTrait;

    private const BASE_URI = 'https://dns.google.com/resolve';

    private const STATUS_OK = 0;
    private const DNS_TYPE_TXT = 16;

    public function verify(string $domain, string $dnsDigest): bool
    {
        $query = [
            'type' => 'TXT',
            'name' => '_acme-challenge.' . $domain,
        ];

        $data = $this->connector
            ->get(self::BASE_URI . '?' . http_build_query($query))
            ->getDecodedContent();

        if ($data['Status'] === self::STATUS_OK) {
            foreach ($data['Answer'] ?? [] as $answer) {
                if ($answer['type'] === self::DNS_TYPE_TXT && $answer['data'] === '"' . $dnsDigest . '"') {
                    return true;
                }
            }
        }

        return false;
    }
}
