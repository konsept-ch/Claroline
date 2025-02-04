<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CursusBundle\Serializer;

use Claroline\AppBundle\API\Options;
use Claroline\AppBundle\API\Serializer\SerializerTrait;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CommunityBundle\Serializer\OrganizationSerializer;
use Claroline\CoreBundle\Entity\Organization\Organization;
use Claroline\CursusBundle\Entity\Quota;
use Claroline\CursusBundle\Entity\Registration\SessionUser;
use Claroline\CursusBundle\Repository\QuotaRepository;

class QuotaSerializer
{
    use SerializerTrait;

    /** @var ObjectManager */
    private $om;

    /** @var OrganizationSerializer */
    private $organizationSerializer;

    /** @var OrganizationRepository */
    private $organizationRepo;

    /** @var QuotaRepository */
    private $quotaRepo;

    public function __construct(ObjectManager $om, OrganizationSerializer $organizationSerializer)
    {
        $this->om = $om;
        $this->organizationSerializer = $organizationSerializer;
        $this->organizationRepo = $om->getRepository(Organization::class);

        $this->quotaRepo = $om->getRepository(Quota::class);
    }

    public function getSchema()
    {
        return '#/plugin/cursus/quota.json';
    }

    public function serialize(Quota $quota, array $options = []): array
    {
        $default = $quota->getDefault();
        $years = $quota->getYears();

        $year = $options['year'] ?? date('Y');

        $serialized = [
            'id' => $quota->getUuid(),
            'organization' => $this->organizationSerializer->serialize($quota->getOrganization(), [Options::SERIALIZE_MINIMAL]),
            'options' => [
                'default' => $default,
                'years' => (object) $years,
            ],
            'quota' => $quota->getQuotaByYear($year),
        ];

        if (isset($options['year'])) {
            $sessionUsers = $this->om->getRepository(SessionUser::class)->findByOrganization($quota->getOrganization(), $options['year']);
            $serialized['pending'] = array_reduce($sessionUsers, fn ($accum, $subscription) => $accum + (SessionUser::STATUS_PENDING == $subscription->getStatus() ? 1 : 0), 0);
        }

        return $serialized;
    }

    public function deserialize(array $data, Quota $quota): Quota
    {
        $this->sipe('id', 'setUuid', $data, $quota);
        $this->sipe('options.default', 'setDefault', $data, $quota);
        $this->sipe('options.years', 'setYears', $data, $quota);

        if (isset($data['organization'])) {
            $organization = $this->organizationRepo->findOneBy(['uuid' => $data['organization']['id']]);
            $quota->setOrganization($organization);
        }

        return $quota;
    }
}
