<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Helper;

trait KeyGeneratorAwareTrait
{
    /**
     * @var KeyGenerator
     */
    private $keyGenerator;

    public function setKeyGenerator(KeyGenerator $keyGenerator): void
    {
        $this->keyGenerator = $keyGenerator;
    }
}
