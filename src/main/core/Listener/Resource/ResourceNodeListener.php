<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Listener\Resource;

use Claroline\AppBundle\Event\Crud\CopyEvent;
use Claroline\AppBundle\Event\Crud\DeleteEvent;

/**
 * Integrates the resource node into Claroline.
 *
 * @todo : move some logic into a manager
 * @todo : move file resource into it's own plugin
 * @todo : maybe use tagged service for file types serialization (see exo items serialization)
 */
class ResourceNodeListener
{
    public function __construct()
    {
    }

    public function onCopy(CopyEvent $event)
    {
        $resourceNode = $event->getCopy();
        $extra = $event->getExtra();

        $resourceNode->setParent($extra['parent']);
        $resourceNode->setCreator($extra['user']);
    }

    public function onDelete(DeleteEvent $event)
    {
        $resourceNode = $event->getObject();
        if (!$resourceNode->isDeletable()) return false;

        $resourceNode->setActive(false);
        return true;
    }
}
