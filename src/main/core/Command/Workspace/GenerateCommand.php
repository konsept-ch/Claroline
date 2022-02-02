<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Command\Workspace;

use Claroline\CursusBundle\Manager\SessionManager;
use Claroline\CursusBundle\Entity\Session;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends Command
{
    private $em;
    private $sessionManager;

    public function __construct(EntityManagerInterface $em, SessionManager $sessionManager)
    {
        $this->em = $em;
        $this->sessionManager = $sessionManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('generate workspace for any sessions');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sessions = $this->em->getRepository(Session::class)->findAll();

        foreach ($sessions as $session)
        {
            $course = $session->getCourse();
            $workspace = $session->getWorkspace();
            if (empty($workspace) && !empty($course)) {
                $workspace = $course->getWorkspace();

                if (empty($workspace)) {
                    $workspace = $this->sessionManager->generateWorkspace($session);
                    $output->writeln($session->getName());
                }
                $session->setWorkspace($workspace);

                $learnerRole = $this->sessionManager->generateRoleForSession(
                    $workspace,
                    $course->getLearnerRoleName(),
                    'learner'
                );
                $session->setLearnerRole($learnerRole);

                $tutorRole = $this->sessionManager->generateRoleForSession(
                    $workspace,
                    $course->getTutorRoleName(),
                    'manager'
                );
                $session->setTutorRole($tutorRole);
            }
        }

        return 0;
    }
}
