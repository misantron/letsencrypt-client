<?php

declare(strict_types=1);

namespace LetsEncrypt\Entity;

final class Identifier extends Entity
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $value;

    public function getValue(): string
    {
        return $this->value;
    }
}
