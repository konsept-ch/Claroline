<?php

namespace Claroline\CursusBundle\Subscriber;

use Claroline\AppBundle\Service\YearScope;
use Claroline\CoreBundle\Event\SearchObjectsEvent;
use Claroline\CursusBundle\Entity\Session;
use Claroline\CursusBundle\Entity\Registration\SessionUser;
use Claroline\CursusBundle\Entity\Event as CursusEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class YearScopeSubscriber implements EventSubscriberInterface
{
    private YearScope $yearScope;

    public function __construct(YearScope $yearScope)
    {
        $this->yearScope = $yearScope;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SearchObjectsEvent::class => 'onSearchObjects',
        ];
    }

    public function onSearchObjects(SearchObjectsEvent $event): void
    {
        $class = $event->getObjectClass();

        // Only target cursus session-related listings
        if (!in_array($class, [Session::class, SessionUser::class, CursusEvent::class], true)) {
            return;
        }

        $filters = $event->getFilters();

        // Respect explicit filters
        if (isset($filters['year']) || isset($filters['yearBefore'])) {
            return;
        }

        if ($this->yearScope->isArchiveMode()) {
            // Archive mode: show items strictly older than previous year
            $filters['yearBefore'] = $this->yearScope->archiveMaxYear();
        } else {
            // Normal mode: restrict to current and previous year
            $filters['year'] = $this->yearScope->normalYearsWindow();
        }

        $event->setFilters($filters);
    }
}
