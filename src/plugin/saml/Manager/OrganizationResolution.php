<?php

namespace Claroline\SamlBundle\Manager;

use Claroline\CoreBundle\Entity\Organization\Organization;

/**
 * The organization found while evaluating an IdP response and how it was found.
 */
class OrganizationResolution
{
    public const CONDITION = 'condition';
    public const IDP_FALLBACK = 'idp_fallback';
    public const NONE = 'none';

    /** @var Organization|null */
    private $organization;
    /** @var string */
    private $source;

    public function __construct(?Organization $organization, string $source)
    {
        $this->organization = $organization;
        $this->source = $source;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function getSource(): string
    {
        return $this->source;
    }
}
