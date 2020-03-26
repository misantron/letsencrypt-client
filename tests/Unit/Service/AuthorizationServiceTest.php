<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019-2020
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Tests\Unit\Service;

use LetsEncrypt\Entity\Authorization;
use LetsEncrypt\Http\DnsCheckerInterface;
use LetsEncrypt\Http\GooglePublicDNS;
use LetsEncrypt\Service\AuthorizationService;
use LetsEncrypt\Tests\ApiClientTestCase;

class AuthorizationServiceTest extends ApiClientTestCase
{
    public function testConstructor(): void
    {
        $service = new AuthorizationService();

        $this->assertPropertyInstanceOf(GooglePublicDNS::class, 'dnsChecker', $service);
    }

    public function testConstructorWithCustomDnsChecker(): void
    {
        $checker = new class () implements DnsCheckerInterface {
            public function verify(string $domain, string $dnsDigest): bool
            {
                return false;
            }
        };

        $service = new AuthorizationService($checker);

        $this->assertPropertyInstanceOf(DnsCheckerInterface::class, 'dnsChecker', $service);
    }

    public function testGetAuthorizations(): void
    {
        $urls = [
            'https://example.com/acme/authz/PAniVnsZcis',
            'https://example.com/acme/authz/r4HqLzrSrpI',
        ];
        $connector = $this->createConnector();

        $this->appendResponseFixture('authorization1.pending.response.json');
        $this->appendResponseFixture('authorization2.pending.response.json');

        $service = new AuthorizationService();
        $service->setConnector($connector);
        $authorizations = $service->getAuthorizations($urls);

        $this->assertInstanceOf(Authorization::class, $authorizations[0]);
        $this->assertSame('https://example.com/acme/authz/PAniVnsZcis', $authorizations[0]->getUrl());
        $this->assertSame('www.example.org', $authorizations[0]->getIdentifierValue());
        $this->assertTrue($authorizations[0]->isPending());

        $this->assertInstanceOf(Authorization::class, $authorizations[1]);
        $this->assertSame('https://example.com/acme/authz/r4HqLzrSrpI', $authorizations[1]->getUrl());
        $this->assertSame('*.example.org', $authorizations[1]->getIdentifierValue());
        $this->assertTrue($authorizations[1]->isPending());
    }
}
