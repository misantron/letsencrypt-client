<?php

declare(strict_types=1);

namespace LetsEncrypt\Entity;

abstract class Entity
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

    public function isFinalized(): bool
    {
        return $this->isValid() || $this->isProcessing();
    }
}
