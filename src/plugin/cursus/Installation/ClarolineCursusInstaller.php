<?php

namespace Claroline\CursusBundle\Installation;

use Claroline\CursusBundle\Installation\Updater\Updater130001;
use Claroline\CursusBundle\Installation\Updater\Updater130013;
use Claroline\CursusBundle\Installation\Updater\Updater130100;
use Claroline\InstallationBundle\Additional\AdditionalInstaller;

class ClarolineCursusInstaller extends AdditionalInstaller
{
    public static function getUpdaters(): array
    {
        return [
            '13.0.1' => Updater130001::class,
            '13.0.13' => Updater130013::class,
            '13.1.0' => Updater130100::class,
        ];
    }

    public function hasFixtures(): bool
    {
        return true;
    }

    public function postInstall()
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
