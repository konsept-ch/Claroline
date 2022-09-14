<?php

namespace Claroline\CursusBundle\Installation\Command;

use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CursusBundle\Entity\Quota;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateQuotasCommand extends Command
{
    /** @var ObjectManager */
    private $om;

    public function __construct(
        ObjectManager $om
    ) {
        $this->om = $om;
    }

    protected function configure()
    {
        $this->setDescription('Installs the platform.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->om->startFlushSuite();

        /**
         * @var Quota[]
         */
        $quotas = $this->om->getRepository(Quota::class)->findAll();

        foreach ($quotas as $quota)
        {
            $quota->setDefault([
                'enabled' => $quota->useQuotas(),
                'quota' => $quota->getThreshold()
            ]);
            $this->om->flush();
        }

        $this->om->endFlushSuite();

        return 0;
    }
}