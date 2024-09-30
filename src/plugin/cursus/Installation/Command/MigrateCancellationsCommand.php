<?php

namespace Claroline\CursusBundle\Installation\Command;

use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CursusBundle\Entity\Registration\SessionCancellation;
use Claroline\CursusBundle\Entity\Registration\SessionUser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCancellationsCommand extends Command
{
    /** @var ObjectManager */
    private $om;

    public function __construct(
        ObjectManager $om
    ) {
        parent::__construct();

        $this->om = $om;
    }

    protected function configure()
    {
        $this->setDescription('Migrate cancellations');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->om->startFlushSuite();

        /**
         * @var SessionCancellation[]
         */
        $cancellations = $this->om->getRepository(SessionCancellation::class)->findAll();
        foreach ($cancellations as $cancellation) {
            $sessionUser = new SessionUser();
            $sessionUser->setSession($cancellation->getSession());
            $sessionUser->setUser($cancellation->getUser());
            $sessionUser->setDate($cancellation->getDate());
            $sessionUser->setCancelled(true);
            $this->om->persist($sessionUser);
        }

        $this->om->endFlushSuite();

        return 0;
    }
}
