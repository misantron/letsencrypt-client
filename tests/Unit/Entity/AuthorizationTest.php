<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019-2020
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Tests\Unit\Entity;

use LetsEncrypt\Entity\Authorization;
use LetsEncrypt\Exception\ChallengeException;
use LetsEncrypt\Tests\TestCase;

class AuthorizationTest extends TestCase
{
    public function testGetHttpChallengeNotExist(): void
    {
        $this->expectException(ChallengeException::class);
        $this->expectExceptionMessage('Http challenge not found in challenge list');

        $authorization = new Authorization(
            [
                'challenges' => [
                    [
                        'type' => 'dns-01',
                        'url' => 'https://example.com/acme/chall/Rg5dV14Gh1Q',
                        'token' => 'DGyRejmCefe7v4NfDGDKfA',
                    ],
                ],
            ],
            'https://example.com/acme/authz/PAniVnsZcis'
        );
        $authorization->getHttpChallenge();
    }

    public function testGetDnsChallengeNotExist(): void
    {
        $this->expectException(ChallengeException::class);
        $this->expectExceptionMessage('Dns challenge not found in challenge list');

        $authorization = new Authorization(
            [
                'challenges' => [
                    [
                        'type' => 'http-01',
                        'url' => 'https://example.com/acme/chall/Rg5dV14Gh1Q',
                        'token' => 'DGyRejmCefe7v4NfDGDKfA',
                    ],
                ],
            ],
            'https://example.com/acme/authz/PAniVnsZcis'
        );
        $authorization->getDnsChallenge();
    }
}
