<?php

declare(strict_types=1);

namespace LetsEncrypt;

use LetsEncrypt\Helper\KeyGenerator;
use LetsEncrypt\Http\Connector;
use LetsEncrypt\Logger\Logger;
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

        $keyGenerator = new KeyGenerator();

        $this->accountService = new AccountService($accountKeysPath);
        $this->accountService->setKeyGenerator($keyGenerator);

        $this->orderService = new OrderService($authorizationService, $certificatesPath);
        $this->orderService->setKeyGenerator($keyGenerator);
    }

    public function account(): AccountService
    {
        return $this->accountService->setConnector($this->connector);
    }

    public function order(): OrderService
    {
        return $this->orderService;
    }
}
