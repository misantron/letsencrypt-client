<?php

declare(strict_types=1);

namespace LetsEncrypt\Tests\Unit\Service;

use LetsEncrypt\Exception\EnvironmentException;
use LetsEncrypt\Service\AuthorizationService;
use LetsEncrypt\Service\OrderService;
use LetsEncrypt\Tests\ApiClientTestCase;

class OrderServiceTest extends ApiClientTestCase
{
    public function testConstructor(): void
    {
        $service = new OrderService(new AuthorizationService(), KEYS_PATH);

        $this->assertPropertyInstanceOf(AuthorizationService::class, 'authorizationService', $service);
        $this->assertPropertySame(KEYS_PATH, 'filesPath', $service);
    }

    public function testConstructorWithInvalidFilesPath(): void
    {
        $this->expectException(EnvironmentException::class);
        $this->expectExceptionMessage('Certificates directory path "notExistDirectory" is not valid');

        new OrderService(new AuthorizationService(), 'notExistDirectory');
    }
}
