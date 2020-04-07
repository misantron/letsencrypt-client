<?php

declare(strict_types=1);

namespace LetsEncrypt\Tests\Mixin;

use LetsEncrypt\Certificate\Bundle;
use LetsEncrypt\Entity\Account;

trait EntityMocksTrait
{
    protected function getAccount(): Account
    {
        return new Account(
            [
                'contact' => [
                    'info@example.com',
                    'tech@example.com',
                ],
            ],
            'https://example.com/acme/acct/evOfKhNU60wg',
            static::getKeysPath() . Bundle::PRIVATE_KEY
        );
    }
}
