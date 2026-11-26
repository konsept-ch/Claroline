<?php

namespace Claroline\CursusBundle\Tests\Unit\Security\Voter;

use Claroline\CoreBundle\Entity\Organization\Organization;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Library\Testing\MockeryTestCase;
use Claroline\CursusBundle\Entity\Quota;
use Claroline\CursusBundle\Security\Voter\QuotaVoter;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * CEP : un gestionnaire RH lisait et validait les inscriptions de n'importe quelle organisation
 * dont il connaissait le quota (constaté sur VAL le 26.11.2026). Il ne doit agir que sur ses
 * organisations et leurs sous-organisations, comme la liste des quotas qu'il voit.
 */
class QuotaVoterTest extends MockeryTestCase
{
    public function testTheQuotaOfHisOwnOrganizationIsAllowed()
    {
        $service = $this->organization('service');

        $this->assertTrue($this->isInUserOrganizations($this->manager($service), $this->quota($service)));
    }

    public function testTheQuotaOfASubOrganizationIsAllowed()
    {
        $service = $this->organization('service');
        $office = $this->organization('office');
        $service->getChildren()->add($office);

        $this->assertTrue($this->isInUserOrganizations($this->manager($service), $this->quota($office)));
    }

    public function testTheQuotaOfAnotherOrganizationIsRefused()
    {
        $service = $this->organization('service');
        $other = $this->organization('other');

        $this->assertFalse($this->isInUserOrganizations($this->manager($service), $this->quota($other)));
    }

    private function isInUserOrganizations(User $user, Quota $quota): bool
    {
        $voter = new QuotaVoter();
        $method = new \ReflectionMethod(QuotaVoter::class, 'isInUserOrganizations');
        $method->setAccessible(true);

        return $method->invoke($voter, new UsernamePasswordToken($user, null, 'main', ['ROLE_USER']), $quota);
    }

    private function organization(string $code): Organization
    {
        $organization = new Organization();
        $organization->setCode($code);
        $organization->setName($code);

        return $organization;
    }

    private function manager(Organization $organization): User
    {
        $user = new User();
        $user->addOrganization($organization);

        return $user;
    }

    private function quota(Organization $organization): Quota
    {
        $quota = new Quota();
        $quota->setOrganization($organization);

        return $quota;
    }
}
