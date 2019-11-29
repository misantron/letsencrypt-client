<?php

declare(strict_types=1);

namespace LetsEncrypt\Entity;

final class Identifier extends Entity
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $value;

    public function getValue(): string
    {
        return $this->value;
    }
}
