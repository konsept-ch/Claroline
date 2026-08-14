<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Tests\Database\Entity;

use Claroline\CoreBundle\Entity\Organization\Organization;
use Claroline\CoreBundle\Entity\Organization\UserOrganizationReference;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Library\Testing\RepositoryTestCase;

class UserOrganizationRemovalTest extends RepositoryTestCase
{
    public function testRemovingAnAdditionalOrganizationDeletesItsReference(): void
    {
        $objectManager = self::$client->getContainer()->get('Claroline\AppBundle\Persistence\ObjectManager');

        $mainOrganization = $this->createOrganization('main-organization');
        $additionalOrganization = $this->createOrganization('additional-organization');
        $user = $this->createUserWithOrganizations($mainOrganization, $additionalOrganization);

        $user->removeOrganization($additionalOrganization);
        $objectManager->flush();

        $remainingReferences = $objectManager
            ->getRepository(UserOrganizationReference::class)
            ->findBy(['user' => $user]);

        $this->assertCount(1, $remainingReferences);
        $this->assertSame($mainOrganization, $remainingReferences[0]->getOrganization());
        $this->assertTrue($remainingReferences[0]->isMain());
    }

    private function createOrganization(string $name): Organization
    {
        $organization = new Organization();
        $organization->setName($name);
        $organization->setEmail($name.'@example.com');

        $objectManager = self::$client->getContainer()->get('Claroline\AppBundle\Persistence\ObjectManager');
        $objectManager->persist($organization);

        return $organization;
    }

    private function createUserWithOrganizations(
        Organization $mainOrganization,
        Organization $additionalOrganization
    ): User {
        $user = self::$client
            ->getContainer()
            ->get('claroline.library.testing.persister')
            ->user('organization-removal-user');
        $user->setMainOrganization($mainOrganization);
        $user->addOrganization($additionalOrganization);

        $objectManager = self::$client->getContainer()->get('Claroline\AppBundle\Persistence\ObjectManager');
        $objectManager->flush();

        return $user;
    }
}
