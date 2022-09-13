<?php

namespace Claroline\CursusBundle\Installation\Updater;

use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CursusBundle\Entity\Quota;
use Claroline\InstallationBundle\Updater\Updater;

class Updater130500 extends Updater
{
    /** @var ObjectManager */
    private $om;

    public function __construct(
        ObjectManager $om
    ) {
        $this->om = $om;
    }

    public function preUpdate()
    {
    }

    public function postUpdate()
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
    }
}
