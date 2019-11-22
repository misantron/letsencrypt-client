<?php

declare(strict_types=1);

namespace LetsEncrypt;

use LetsEncrypt\Http\Connector;
use LetsEncrypt\Http\Logger;
use LetsEncrypt\Service\AccountService;
use LetsEncrypt\Service\AuthorizationService;
use LetsEncrypt\Service\OrderService;

class Client
{
    /**
     * @var Connector
     */
    private $connector;

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
        $this->connector = new Connector($staging, $logger);

        $authorizationService = new AuthorizationService();
        $authorizationService->setConnector($this->connector);

        $this->accountService = new AccountService($accountKeysPath);
        $this->orderService = new OrderService($authorizationService, $certificatesPath);
    }

    public function account(): AccountService
    {
        return $this->accountService
            ->setConnector($this->connector);
    }

    public function order(): OrderService
    {
        return $this->orderService
            ->setConnector($this->connector);
    }
}
