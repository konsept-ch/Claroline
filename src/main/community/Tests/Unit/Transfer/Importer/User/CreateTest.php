<?php

namespace Claroline\CommunityBundle\Tests\Unit\Transfer\Importer\User;

use Claroline\AppBundle\API\Crud;
use Claroline\AppBundle\API\Options;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CommunityBundle\Transfer\Importer\User\Create;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Library\Testing\MockeryTestCase;

/**
 * CEP : un compte importé doit avoir son espace personnel, sinon ses attestations n'ont
 * nulle part où aller. L'amont ne le créait que si le fichier le demandait.
 */
class CreateTest extends MockeryTestCase
{
    public function testThePersonalWorkspaceIsCreatedByDefault()
    {
        $options = $this->import(['username' => 'jdoe']);

        $this->assertNotContains(Options::NO_PERSONAL_WORKSPACE, $options);
    }

    public function testThePersonalWorkspaceIsCreatedWhenRequested()
    {
        $options = $this->import(['username' => 'jdoe', 'meta' => ['personalWorkspace' => true]]);

        $this->assertNotContains(Options::NO_PERSONAL_WORKSPACE, $options);
    }

    public function testAnExplicitFalseIsRespected()
    {
        $options = $this->import(['username' => 'jdoe', 'meta' => ['personalWorkspace' => false]]);

        $this->assertContains(Options::NO_PERSONAL_WORKSPACE, $options);
    }

    public function testANullValueMeansNotSpecified()
    {
        $options = $this->import(['username' => 'jdoe', 'meta' => ['personalWorkspace' => null]]);

        $this->assertNotContains(Options::NO_PERSONAL_WORKSPACE, $options);
    }

    /**
     * Lance l'import d'une ligne et renvoie les options passées à la création du compte.
     */
    private function import(array $data): array
    {
        $received = null;

        $crud = $this->mock(Crud::class);
        $crud->shouldReceive('create')
            ->once()
            ->withArgs(function ($class, $payload, $options) use (&$received) {
                $received = $options;

                return User::class === $class;
            })
            ->andReturn(new User());

        $importer = new Create($crud, $this->mock(ObjectManager::class));
        $importer->execute($data);

        $this->assertContains(Options::FORCE_FLUSH, $received);

        return $received;
    }
}
