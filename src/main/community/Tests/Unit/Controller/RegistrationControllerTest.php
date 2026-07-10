<?php

namespace Claroline\CommunityBundle\Tests\Unit\Controller;

use Claroline\AppBundle\API\Crud;
use Claroline\AppBundle\API\Options;
use Claroline\AppBundle\API\SerializerProvider;
use Claroline\AppBundle\Manager\TermsOfServiceManager;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\AuthenticationBundle\Security\Authentication\Authenticator;
use Claroline\CommunityBundle\Controller\RegistrationController;
use Claroline\CoreBundle\API\Serializer\User\ProfileSerializer;
use Claroline\CoreBundle\Entity\Organization\Organization;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler;
use Claroline\CoreBundle\Library\Testing\MockeryTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class RegistrationControllerTest extends MockeryTestCase
{
    public function testRegisterActionAssignsSelectedOrganizationAsMainOrganization()
    {
        $authorization = $this->mock(AuthorizationCheckerInterface::class);
        $authorization->shouldReceive('isGranted')
            ->once()
            ->with('IS_AUTHENTICATED_FULLY')
            ->andReturn(false);

        $om = $this->mock(ObjectManager::class);
        $organizationRepository = \Mockery::mock();
        $organization = new Organization();
        $organization->setName('Commune d\'Eysins');
        $organization->setCode('EYSINS');

        $om->shouldReceive('getRepository')
            ->once()
            ->with(Organization::class)
            ->andReturn($organizationRepository);
        $organizationRepository->shouldReceive('findOneBy')
            ->once()
            ->with(['code' => 'EYSINS'])
            ->andReturn($organization);

        $config = $this->mock(PlatformConfigurationHandler::class);
        $config->shouldReceive('getParameter')
            ->with('registration.self')
            ->andReturn(true);
        $config->shouldReceive('getParameter')
            ->with('registration.organization_selection')
            ->andReturn('select');
        $config->shouldReceive('getParameter')
            ->with('registration.auto_logging')
            ->andReturn(false);
        $config->shouldReceive('getParameter')
            ->with('registration.validation')
            ->andReturn(0);

        $crud = $this->mock(Crud::class);
        $payload = [
            'email' => 'habitats@eysins.ch',
            'firstName' => 'Nadine',
            'lastName' => 'Massarutto',
            'username' => 'nmassarutto',
            'plainPassword' => 'secret',
            'mainOrganization' => [
                'id' => 'org-uuid',
                'code' => 'EYSINS',
                'name' => 'Commune d\'Eysins',
            ],
        ];
        $user = new User();
        $user->setEmail('habitats@eysins.ch');
        $user->setFirstName('Nadine');
        $user->setLastName('Massarutto');
        $user->setUsername('nmassarutto');

        $crud->shouldReceive('create')
            ->once()
            ->withArgs(function ($class, $data, $options) use ($payload) {
                return User::class === $class
                    && $data === $payload
                    && in_array(Options::REGISTRATION, $options, true)
                    && in_array(Options::ADD_NOTIFICATIONS, $options, true)
                    && in_array(Options::NO_PERSONAL_WORKSPACE, $options, true)
                    && in_array(Options::WORKSPACE_VALIDATE_ROLES, $options, true)
                    && in_array(Options::VALIDATE_FACET, $options, true);
            })
            ->andReturn($user);
        $serializer = $this->mock(SerializerProvider::class);
        $serializer->shouldReceive('serialize')
            ->once()
            ->with($user)
            ->andReturn(['id' => 'user-uuid']);

        $profileSerializer = $this->mock(ProfileSerializer::class);
        $termsOfServiceManager = $this->mock(TermsOfServiceManager::class);
        $authenticator = $this->mock(Authenticator::class);

        $controller = new RegistrationController(
            $authorization,
            $om,
            $config,
            $crud,
            $serializer,
            $profileSerializer,
            $termsOfServiceManager,
            $authenticator
        );

        $response = $controller->registerAction(
            Request::create(
                '/user/registration',
                'POST',
                [],
                [],
                [],
                [],
                json_encode($payload)
            )
        );

        $this->assertSame(204, $response->getStatusCode());
    }

    public function testRegisterActionDoesNotOverrideMissingOrganization()
    {
        $authorization = $this->mock(AuthorizationCheckerInterface::class);
        $authorization->shouldReceive('isGranted')
            ->once()
            ->with('IS_AUTHENTICATED_FULLY')
            ->andReturn(false);

        $om = $this->mock(ObjectManager::class);
        $om->shouldReceive('getRepository')->once()->with(Organization::class);

        $config = $this->mock(PlatformConfigurationHandler::class);
        $config->shouldReceive('getParameter')
            ->with('registration.self')
            ->andReturn(true);
        $config->shouldReceive('getParameter')
            ->with('registration.organization_selection')
            ->andReturn('select');
        $config->shouldReceive('getParameter')
            ->with('registration.auto_logging')
            ->andReturn(false);
        $config->shouldReceive('getParameter')
            ->with('registration.validation')
            ->andReturn(0);

        $crud = $this->mock(Crud::class);
        $payload = [
            'email' => 'no-org@example.com',
            'firstName' => 'No',
            'lastName' => 'Organization',
            'username' => 'noorg',
            'plainPassword' => 'secret',
        ];
        $user = new User();
        $user->setEmail('no-org@example.com');
        $user->setFirstName('No');
        $user->setLastName('Organization');
        $user->setUsername('noorg');

        $crud->shouldReceive('create')
            ->once()
            ->andReturn($user);
        $crud->shouldReceive('replace')->never();

        $serializer = $this->mock(SerializerProvider::class);
        $serializer->shouldReceive('serialize')
            ->once()
            ->with($user)
            ->andReturn(['id' => 'user-uuid']);

        $profileSerializer = $this->mock(ProfileSerializer::class);
        $termsOfServiceManager = $this->mock(TermsOfServiceManager::class);
        $authenticator = $this->mock(Authenticator::class);

        $controller = new RegistrationController(
            $authorization,
            $om,
            $config,
            $crud,
            $serializer,
            $profileSerializer,
            $termsOfServiceManager,
            $authenticator
        );

        $response = $controller->registerAction(
            Request::create(
                '/user/registration',
                'POST',
                [],
                [],
                [],
                [],
                json_encode($payload)
            )
        );

        $this->assertSame(204, $response->getStatusCode());
    }
}
