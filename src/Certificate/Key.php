<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

namespace LetsEncrypt\Certificate;

use LetsEncrypt\Enum\ECKeyAlgorithm;
use LetsEncrypt\Enum\KeyType;
use LetsEncrypt\Enum\RSAKeyLength;

final class Key
{
    /**
     * @var KeyType
     */
    private $type;

    /**
     * @var RSAKeyLength
     */
    private $length;

    /**
     * @var ECKeyAlgorithm
     */
    private $algorithm;

    private function __construct(KeyType $type)
    {
        $this->type = $type;
    }

    public static function rsa(RSAKeyLength $length): self
    {
        $key = new static(KeyType::rsa());

        return $key->setLength($length);
    }

    public static function ec(ECKeyAlgorithm $algorithm): self
    {
        $key = new static(KeyType::ec());

        return $key->setAlgorithm($algorithm);
    }

    public function isRSA(): bool
    {
        return $this->type->isEqual('rsa');
    }

    public function isEC(): bool
    {
        return $this->type->isEqual('ec');
    }

    public function getLength(): ?RSAKeyLength
    {
        return $this->length;
    }

    public function getAlgorithm(): ?ECKeyAlgorithm
    {
        return $this->algorithm;
    }

    private function setLength(RSAKeyLength $length): self
    {
        $this->length = $length;

        return $this;
    }

    private function setAlgorithm(ECKeyAlgorithm $algorithm): self
    {
        $this->algorithm = $algorithm;

        return $this;
    }
}
