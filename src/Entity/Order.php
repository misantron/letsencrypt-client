<?php

declare(strict_types=1);

namespace LetsEncrypt\Entity;

final class Order extends Entity
{
    use UrlAwareTrait;

    /**
     * @var string
     */
    public $expires;

    /**
     * Order identifiers
     * @example {'type':'dns', 'value':'*.example.com'}
     * @var Identifier[]
     */
    protected $identifiers;

    /**
     * @var Authorization[]
     */
    protected $authorizations;

    /**
     * Order finalize URL
     * @var string
     */
    protected $finalize;

    /**
     * Certificate request URL
     * @var string
     */
    protected $certificate;

    public function __construct(array $data, string $url)
    {
        // extract only value from identifiers data
        $data['identifiers'] = array_map(static function (array $entry) {
            return $entry['value'];
        }, $data['identifiers']);

        parent::__construct($data);

        $this->url = $url;
    }

    public function isIdentifiersEqual(array $subjects): bool
    {
        $identifiers = $this->identifiers;

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
        foreach ($this->authorizations as $authorization) {
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
        return $this->authorizations;
    }

    public function allAuthorizationsValid(): bool
    {
        foreach ($this->authorizations as $authorization) {
            if (!$authorization->isValid()) {
                return false;
            }
        }
        return true;
    }

    public function getIdentifiers(): array
    {
        return $this->identifiers;
    }

    public function getFinalizeUrl(): string
    {
        return $this->finalize;
    }

    public function getCertificateRequestUrl(): string
    {
        return $this->certificate;
    }
}
