<?php

namespace Claroline\HomeBundle\Subscriber;

use Claroline\HomeBundle\Cache\HomeCache;
use Claroline\HomeBundle\Entity\HomeTab;
use Claroline\HomeBundle\Entity\Type\AbstractTab;
use Claroline\CoreBundle\Entity\Widget\Widget;
use Claroline\CoreBundle\Entity\Widget\WidgetContainer;
use Claroline\CoreBundle\Entity\Widget\WidgetContainerConfig;
use Claroline\CoreBundle\Entity\Widget\WidgetInstance;
use Claroline\CoreBundle\Entity\Widget\WidgetInstanceConfig;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

class HomeCacheInvalidationSubscriber implements EventSubscriber
{
    /** @var HomeCache */
    private $cache;

    /** @var bool */
    private $needsInvalidation = false;

    public function __construct(HomeCache $cache)
    {
        $this->cache = $cache;
    }

    public function getSubscribedEvents(): array
    {
        return ['postPersist', 'postUpdate', 'postRemove', 'postFlush'];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->scheduleInvalidation($args->getObject());
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->scheduleInvalidation($args->getObject());
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $this->scheduleInvalidation($args->getObject());
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if (!$this->needsInvalidation) {
            return;
        }

        $this->cache->invalidateAll();
        $this->needsInvalidation = false;
    }

    private function scheduleInvalidation($entity): void
    {
        if ($this->needsInvalidation) {
            return;
        }

        if ($entity instanceof HomeTab ||
            $entity instanceof AbstractTab ||
            $entity instanceof Widget ||
            $entity instanceof WidgetContainer ||
            $entity instanceof WidgetContainerConfig ||
            $entity instanceof WidgetInstance ||
            $entity instanceof WidgetInstanceConfig
        ) {
            $this->needsInvalidation = true;
        }
    }
}
