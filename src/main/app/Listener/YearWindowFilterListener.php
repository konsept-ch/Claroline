<?php

namespace Claroline\AppBundle\Listener;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class YearWindowFilterListener
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $em = $this->registry->getManager();
        if (!$em->getFilters()->has('year_window')) {
            return;
        }

        $filter = $em->getFilters()->enable('year_window');

        $now = new \DateTimeImmutable('now');
        $year = (int) $now->format('Y');
        $cutoffStart = (new \DateTimeImmutable(($year - 1) . '-01-01 00:00:00'));

        $archiveMode = getenv('ARCHIVE_MODE');
        $archive = (is_string($archiveMode) && trim($archiveMode) !== '' && (int)$archiveMode === 1) ? 1 : 0;

        $filter->setParameter('cutoffStart', $cutoffStart->format('Y-m-d H:i:s'));
        $filter->setParameter('archive', $archive);
    }
}