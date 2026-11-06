<?php

namespace Claroline\CursusBundle\Tests\Unit\Controller\User;

use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\Resource\ResourceNode;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Claroline\CoreBundle\Library\Testing\MockeryTestCase;
use Claroline\CursusBundle\Controller\User\AttestationController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AttestationControllerTest extends MockeryTestCase
{
    public function testAnonymousVisitorIsSentToLogin()
    {
        $om = $this->mock(ObjectManager::class);
        $om->shouldNotReceive('getRepository');

        $response = $this->controllerFor('anon.', $om)->openAction(new Request());

        $this->assertSame('/#/login', $response->getTargetUrl());
    }

    public function testOpensMesAttestationsFolderWhenItExists()
    {
        $workspace = new Workspace();
        $root = $this->node('default-personal-18');
        $folder = $this->node('mes-attestations-42');

        $repository = \Mockery::mock();
        $repository->shouldReceive('findWorkspaceRoot')->once()->with($workspace)->andReturn($root);
        $repository->shouldReceive('findOneBy')
            ->once()
            ->with(['parent' => $root, 'name' => 'Mes attestations', 'active' => true], ['id' => 'ASC'])
            ->andReturn($folder);

        $response = $this->controllerFor($this->userWith($workspace), $this->omWith($repository))
            ->openAction(new Request());

        $this->assertSame('/#/desktop/resources/mes-attestations-42', $response->getTargetUrl());
    }

    public function testFallsBackToPersonalRootWithoutMesAttestations()
    {
        $workspace = new Workspace();
        $root = $this->node('sylvain-pecoud-levaud-ch');

        $repository = \Mockery::mock();
        $repository->shouldReceive('findWorkspaceRoot')->once()->andReturn($root);
        $repository->shouldReceive('findOneBy')->once()->andReturn(null);

        $response = $this->controllerFor($this->userWith($workspace), $this->omWith($repository))
            ->openAction(new Request());

        $this->assertSame('/#/desktop/resources/sylvain-pecoud-levaud-ch', $response->getTargetUrl());
    }

    public function testUserWithoutPersonalWorkspaceLandsOnResources()
    {
        $om = $this->mock(ObjectManager::class);
        $om->shouldNotReceive('getRepository');

        $response = $this->controllerFor(new User(), $om)->openAction(new Request());

        $this->assertSame('/#/desktop/resources', $response->getTargetUrl());
    }

    private function controllerFor($user, ObjectManager $om): AttestationController
    {
        $token = $this->mock(TokenInterface::class);
        $token->shouldReceive('getUser')->andReturn($user);

        $tokenStorage = $this->mock(TokenStorageInterface::class);
        $tokenStorage->shouldReceive('getToken')->andReturn($token);

        return new AttestationController($tokenStorage, $om);
    }

    private function omWith($repository): ObjectManager
    {
        $om = $this->mock(ObjectManager::class);
        $om->shouldReceive('getRepository')->with(ResourceNode::class)->andReturn($repository);

        return $om;
    }

    private function userWith(Workspace $workspace): User
    {
        $user = new User();
        $user->setPersonalWorkspace($workspace);

        return $user;
    }

    private function node(string $slug): ResourceNode
    {
        $node = new ResourceNode();
        $node->setSlug($slug);

        return $node;
    }
}
