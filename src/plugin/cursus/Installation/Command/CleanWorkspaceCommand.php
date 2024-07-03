<?php

namespace Claroline\CursusBundle\Installation\Command;

use Claroline\AppBundle\API\Crud;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Manager\Workspace\WorkspaceManager;
use Claroline\CursusBundle\Entity\Registration\SessionUser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanWorkspaceCommand extends Command
{
    /** @var ObjectManager */
    private $om;
    /** @var WorkspaceManager */
    private $workspaceManager;

    public function __construct(
        ObjectManager $om,
        WorkspaceManager $workspaceManager
    ) {
        parent::__construct();

        $this->om = $om;
        $this->workspaceManager = $workspaceManager;
    }

    protected function configure()
    {
        $this->setDescription('Installs the platform.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->om->startFlushSuite();

        /**
         * @var SessionUser[]
         */
        $sessionUsers = $this->om->getRepository(SessionUser::class)->findAll();

        foreach ($sessionUsers as $sessionUser) {
            if ((SessionUser::STATUS_REFUSED != $sessionUser->getStatus() && SessionUser::STATUS_CANCELLED != $sessionUser->getStatus()) || !$sessionUser->getSession()->getWorkspace()) continue;

            $this->workspaceManager->unregister($sessionUser->getUser(), $sessionUser->getSession()->getWorkspace(), [Crud::NO_PERMISSIONS]);
        }

        $this->om->endFlushSuite();

        return 0;
    }
}
