<?php

declare(strict_types=1);

namespace LetsEncrypt\Entity;

final class Order extends Entity
{
    public $expires;
    public $identifiers;
    public $authorizations;
    public $finalize;
    public $certificate;

    /**
     * @var Authorization[]
     */
    private $authorizationsData;

    public function __construct(array $data, array $authorizationsData)
    {
        parent::__construct($data);

        $this->authorizationsData = $authorizationsData;
    }

    public function isIdentifiersEqual(array $subjects): bool
    {
        $identifiers = array_map(static function (array $entry) {
            return $entry['value'];
        }, $this->identifiers);

        sort($identifiers, SORT_STRING);
        sort($subjects, SORT_STRING);

        return $identifiers === $subjects;
    }

    /**
     * @return Authorization[]
     */
    public function getPendingAuthorizations(): array
    {
        $authorizations = [];
        foreach ($this->authorizationsData as $authorization) {
            if ($authorization->isPending()) {
                $authorizations[] = $authorization;
            }
        }
        return $authorizations;
    }

    /**
     * @return Authorization[]
     */
    public function getAuthorizations(): array
    {
        return $this->authorizationsData;
    }

    public function allAuthorizationsValid(): bool
    {
        foreach ($this->authorizationsData as $authorization) {
            if (!$authorization->isValid()) {
                return false;
            }
        }
        return true;
    }
}
