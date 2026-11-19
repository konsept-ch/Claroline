<?php

namespace Claroline\CoreBundle\Tests\Unit\Manager\Workspace;

use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\Resource\ResourceNode;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Claroline\CoreBundle\Library\Testing\MockeryTestCase;
use Claroline\CoreBundle\Manager\Workspace\PersonalWorkspaceProvisioner;
use Claroline\CoreBundle\Manager\Workspace\WorkspaceManager;

/**
 * Un compte cree par la personne elle-meme n'a pas d'espace personnel depuis le 09.06.2026,
 * et ses attestations n'ont nulle part ou aller (signale par le client le 19.11.2026).
 *
 * Constate sur une copie de la production : parmi les comptes sans espace, certains ont
 * deja un espace a leur code. 57 sont des copies interrompues, personnelles, rattachees a
 * personne, reduites a leur racine. 5 sont l'espace d'un autre compte : on n'y touche pas.
 */
class PersonalWorkspaceProvisionerTest extends MockeryTestCase
{
    private $om;
    private $workspaceManager;
    private $workspaceRepo;
    private $userRepo;
    private $nodeRepo;

    public function testNothingIsDoneWhenTheWorkspaceExists()
    {
        $user = $this->user();
        $user->setPersonalWorkspace($this->workspace(1, 'jdoe', true));

        $this->workspaceRepo->shouldNotReceive('findOneBy');
        $this->workspaceManager->shouldNotReceive('createPersonalWorkspace');

        $this->assertSame(PersonalWorkspaceProvisioner::EXISTING, $this->provisioner()->ensure($user));
    }

    public function testTheWorkspaceIsCreatedWhenTheCodeIsFree()
    {
        $user = $this->user();
        $this->workspaceRepo->shouldReceive('findOneBy')->with(['code' => 'jdoe'])->andReturn(null);
        $this->workspaceManager->shouldReceive('createPersonalWorkspace')->once()->with($user);

        $this->assertSame(PersonalWorkspaceProvisioner::CREATED, $this->provisioner()->ensure($user));
    }

    public function testAnInterruptedCopyIsRenamedThenTheWorkspaceIsCreated()
    {
        $user = $this->user();
        $orphan = $this->workspace(31808, 'jdoe', true);
        $this->workspaceRepo->shouldReceive('findOneBy')->with(['code' => 'jdoe'])->andReturn($orphan);
        $this->userRepo->shouldReceive('findOneBy')->with(['personalWorkspace' => $orphan])->andReturn(null);
        $this->nodeRepo->shouldReceive('count')->with(['workspace' => $orphan])->andReturn(1);

        $this->om->shouldReceive('persist')->once()->with($orphan);
        $this->om->shouldReceive('flush')->once();
        $this->workspaceManager->shouldReceive('createPersonalWorkspace')->once()->with($user);

        $this->assertSame(PersonalWorkspaceProvisioner::CREATED_AFTER_ORPHAN, $this->provisioner()->ensure($user));
        // renomme, pas supprime : l'espace reste consultable pour la verification
        $this->assertSame('jdoe~orphan-31808', $orphan->getCode());
    }

    public function testTheWorkspaceOfAnotherUserIsNeverTouched()
    {
        $user = $this->user();
        $taken = $this->workspace(40, 'jdoe', true);
        $this->workspaceRepo->shouldReceive('findOneBy')->andReturn($taken);
        $this->userRepo->shouldReceive('findOneBy')->with(['personalWorkspace' => $taken])->andReturn(new User());

        $this->om->shouldNotReceive('persist');
        $this->workspaceManager->shouldNotReceive('createPersonalWorkspace');

        $this->assertSame(PersonalWorkspaceProvisioner::CODE_TAKEN, $this->provisioner()->ensure($user));
        $this->assertSame('jdoe', $taken->getCode());
    }

    public function testANonPersonalWorkspaceIsNeverTouched()
    {
        $user = $this->user();
        $this->workspaceRepo->shouldReceive('findOneBy')->andReturn($this->workspace(41, 'jdoe', false));

        $this->om->shouldNotReceive('persist');
        $this->workspaceManager->shouldNotReceive('createPersonalWorkspace');

        $this->assertSame(PersonalWorkspaceProvisioner::CODE_TAKEN, $this->provisioner()->ensure($user));
    }

    public function testAnOrphanWithContentIsNeverTouched()
    {
        $user = $this->user();
        $orphan = $this->workspace(42, 'jdoe', true);
        $this->workspaceRepo->shouldReceive('findOneBy')->andReturn($orphan);
        $this->userRepo->shouldReceive('findOneBy')->andReturn(null);
        $this->nodeRepo->shouldReceive('count')->andReturn(3);

        $this->om->shouldNotReceive('persist');
        $this->workspaceManager->shouldNotReceive('createPersonalWorkspace');

        $this->assertSame(PersonalWorkspaceProvisioner::CODE_TAKEN, $this->provisioner()->ensure($user));
        $this->assertSame('jdoe', $orphan->getCode());
    }

    public function testDiagnoseWritesNothing()
    {
        $user = $this->user();
        $orphan = $this->workspace(31808, 'jdoe', true);
        $this->workspaceRepo->shouldReceive('findOneBy')->andReturn($orphan);
        $this->userRepo->shouldReceive('findOneBy')->andReturn(null);
        $this->nodeRepo->shouldReceive('count')->andReturn(1);

        $this->om->shouldNotReceive('persist');
        $this->om->shouldNotReceive('flush');
        $this->workspaceManager->shouldNotReceive('createPersonalWorkspace');

        $this->assertSame(PersonalWorkspaceProvisioner::CREATED_AFTER_ORPHAN, $this->provisioner()->diagnose($user));
        $this->assertSame('jdoe', $orphan->getCode());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->workspaceRepo = \Mockery::mock();
        $this->userRepo = \Mockery::mock();
        $this->nodeRepo = \Mockery::mock();

        $this->om = $this->mock(ObjectManager::class);
        $this->om->shouldReceive('getRepository')->with(Workspace::class)->andReturn($this->workspaceRepo);
        $this->om->shouldReceive('getRepository')->with(User::class)->andReturn($this->userRepo);
        $this->om->shouldReceive('getRepository')->with(ResourceNode::class)->andReturn($this->nodeRepo);

        $this->workspaceManager = $this->mock(WorkspaceManager::class);
    }

    private function provisioner(): PersonalWorkspaceProvisioner
    {
        return new PersonalWorkspaceProvisioner($this->om, $this->workspaceManager);
    }

    private function user(): User
    {
        $user = new User();
        $user->setUsername('jdoe');

        return $user;
    }

    private function workspace(int $id, string $code, bool $personal): Workspace
    {
        $workspace = new Workspace();
        $workspace->setId($id);
        $workspace->setCode($code);
        $workspace->setPersonal($personal);

        return $workspace;
    }
}
