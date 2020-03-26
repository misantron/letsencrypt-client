<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019-2020
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Tests\Unit\Logger;

use LetsEncrypt\Logger\Logger;
use LetsEncrypt\Logger\LogStrategy;
use LetsEncrypt\Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\Test\TestLogger;

class LoggerTest extends TestCase
{
    public function testConstructorWithDefaults(): void
    {
        $logger = new Logger(new TestLogger());

        $this->assertPropertyInstanceOf(LoggerInterface::class, 'origin', $logger);
        $this->assertPropertyInstanceOf(LogStrategy::class, 'strategy', $logger);
        $this->assertTrue($logger->logErrorsOnly());
    }

    public function testConstructorWithCustomStrategy(): void
    {
        $logger = new Logger(new TestLogger(), LogStrategy::debugMode());

        $this->assertFalse($logger->logErrorsOnly());
        $this->assertTrue($logger->debugMode());
    }

    public function testLogRequestsOnly(): void
    {
        $logger = new Logger(new TestLogger(), LogStrategy::requestsOnly());

        $this->assertTrue($logger->logRequestsOnly());
    }

    public function testLog(): void
    {
        $message = 'debug';
        $context = [
            'foo' => 'bar',
        ];

        $originMock = $this->createMock(LoggerInterface::class);
        $originMock
            ->expects($this->once())
            ->method('log')
            ->with(LogLevel::INFO, $message, $context);

        $logger = new Logger($originMock);
        $logger->log(LogLevel::INFO, $message, $context);
    }

    public function testGetLogLevel(): void
    {
        $logger = new Logger(new TestLogger());

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(201);

        $this->assertSame(LogLevel::INFO, $logger->getLogLevel($response));

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(403);

        $this->assertSame(LogLevel::ERROR, $logger->getLogLevel($response));
    }
}
