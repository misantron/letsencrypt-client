<?php

declare(strict_types=1);

namespace LetsEncrypt;

use LetsEncrypt\Http\Connector;
use LetsEncrypt\Http\Logger;
use LetsEncrypt\Service\AccountService;
use LetsEncrypt\Service\OrderService;

class Client
{
    /**
     * @var AccountService
     */
    private $accountService;

    /**
     * @var OrderService
     */
    private $orderService;

    public function __construct(
        string $accountKeysPath,
        string $certificatesPath,
        bool $staging = true,
        Logger $logger = null
    ) {
        $connector = new Connector($staging, $logger);

        $this->accountService = new AccountService($connector, $accountKeysPath);
        $this->orderService = new OrderService($connector, $certificatesPath);
    }

    public function account(): AccountService
    {
        return $this->accountService;
    }

    public function order(): OrderService
    {
        return $this->orderService;
    }
}
