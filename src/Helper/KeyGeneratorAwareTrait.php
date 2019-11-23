<?php

declare(strict_types=1);

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
