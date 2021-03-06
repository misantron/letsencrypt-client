<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019-2020
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Entity;

abstract class Entity implements \JsonSerializable
{
    private const STATUS_PENDING = 'pending';
    private const STATUS_VALID = 'valid';
    private const STATUS_READY = 'ready';
    private const STATUS_INVALID = 'invalid';
    private const STATUS_PROCESSING = 'processing';

    /**
     * @var string
     */
    protected $status;

    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    public function isInvalid(): bool
    {
        return $this->status === self::STATUS_INVALID;
    }

    public function isValid(): bool
    {
        return $this->status === self::STATUS_VALID;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function jsonSerialize(): array
    {
        $reflect = new \ReflectionClass($this);

        $output = [];
        foreach ($reflect->getProperties() as $property) {
            $property->setAccessible(true);
            $output[$property->getName()] = $property->getValue($this);
        }

        return $output;
    }
}
