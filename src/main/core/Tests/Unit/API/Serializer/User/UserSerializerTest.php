<?php

namespace Claroline\CoreBundle\Tests\Unit\API\Serializer\User;

use Claroline\AppBundle\API\Options;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\API\Serializer\File\PublicFileSerializer;
use Claroline\CoreBundle\API\Serializer\User\OrganizationSerializer;
use Claroline\CoreBundle\API\Serializer\User\UserSerializer;
use Claroline\CoreBundle\Entity\Organization\Organization;
use Claroline\CoreBundle\Entity\Facet\FieldFacet;
use Claroline\CoreBundle\Entity\Facet\FieldFacetValue;
use Claroline\CoreBundle\Entity\Role;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler;
use Claroline\CoreBundle\Library\Testing\MockeryTestCase;
use Claroline\CoreBundle\Manager\FacetManager;
use Claroline\CoreBundle\Manager\Workspace\WorkspaceUserQueueManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class UserSerializerTest extends MockeryTestCase
{
    public function testDeserializeAssignsMainOrganizationDuringRegistration(): void
    {
        $om = $this->mock(ObjectManager::class);
        $organizationRepository = \Mockery::mock();
        $roleRepository = \Mockery::mock();
        $fieldFacetRepository = \Mockery::mock();
        $fieldFacetValueRepository = \Mockery::mock();

        $om->shouldReceive('getRepository')
            ->with(Organization::class)
            ->andReturn($organizationRepository);
        $om->shouldReceive('getRepository')
            ->with(Role::class)
            ->andReturn($roleRepository);
        $om->shouldReceive('getRepository')
            ->with(FieldFacet::class)
            ->andReturn($fieldFacetRepository);
        $om->shouldReceive('getRepository')
            ->with(FieldFacetValue::class)
            ->andReturn($fieldFacetValueRepository);

        $organization = new Organization();
        $organization->setName('Commune de Lussy-sur-Morges');
        $organization->setCode('LUSSY');

        $om->shouldReceive('getObject')
            ->once()
            ->with(
                [
                    'id' => 'org-uuid',
                    'code' => 'LUSSY',
                    'name' => 'Commune de Lussy-sur-Morges',
                    'email' => null,
                ],
                Organization::class,
                ['id', 'code', 'name', 'email']
            )
            ->andReturn($organization);

        $serializer = new UserSerializer(
            $this->mock(TokenStorageInterface::class),
            $this->mock(AuthorizationCheckerInterface::class),
            $om,
            $this->mock(PlatformConfigurationHandler::class),
            $this->mock(PublicFileSerializer::class),
            $this->mock(\Claroline\AppBundle\Event\StrictDispatcher::class),
            new OrganizationSerializer($om),
            $this->mock(FacetManager::class),
            $this->mock(WorkspaceUserQueueManager::class)
        );

        $user = $serializer->deserialize([
            'email' => 'rex@test.ch',
            'firstName' => 'Rex',
            'lastName' => 'Test',
            'plainPassword' => 'StrongPass123!',
            'mainOrganization' => [
                'id' => 'org-uuid',
                'code' => 'LUSSY',
                'name' => 'Commune de Lussy-sur-Morges',
                'email' => null,
            ],
        ], null, [Options::REGISTRATION]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame($organization, $user->getMainOrganization());
    }
}
