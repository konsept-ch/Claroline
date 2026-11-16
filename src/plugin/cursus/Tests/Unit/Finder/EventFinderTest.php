<?php

namespace Claroline\CursusBundle\Tests\Unit\Finder;

use Claroline\CursusBundle\Entity\Event;
use Claroline\CursusBundle\Finder\EventFinder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Une séance sans date de début doit rester visible dans les listes.
 *
 * Le filtre ARCHIVE_MODE compare l'année de la date de début : une date nulle n'y passait
 * jamais, et la séance disparaissait de toutes les listes (incident ENT_LS/27/03, 13.11.2026).
 */
class EventFinderTest extends TestCase
{
    private $archiveMode;

    public function testListsEventsWithoutStartDate()
    {
        putenv('ARCHIVE_MODE=0');

        $dql = $this->dql();

        $this->assertStringContainsString('year(po.startDate) > :year', $dql);
        $this->assertStringContainsString('po.startDate IS NULL', $dql);
    }

    public function testNotEndedFilterKeepsEventsWithoutEndDate()
    {
        putenv('ARCHIVE_MODE=0');

        // filtre par défaut de l'onglet Séances : une séance sans date n'est pas terminée
        $dql = $this->dql(['status' => 'not_ended']);

        $this->assertStringContainsString('po.endDate >= :now', $dql);
        $this->assertStringContainsString('po.endDate IS NULL', $dql);
    }

    public function testArchiveModeKeepsOnlyPastEvents()
    {
        putenv('ARCHIVE_MODE=1');

        $dql = $this->dql();

        $this->assertStringContainsString('year(po.startDate) <= :year', $dql);
        // une séance sans date n'est pas une séance archivée
        $this->assertStringNotContainsString('po.startDate IS NULL', $dql);
    }

    protected function setUp(): void
    {
        $this->archiveMode = getenv('ARCHIVE_MODE');
    }

    protected function tearDown(): void
    {
        putenv(false === $this->archiveMode ? 'ARCHIVE_MODE' : 'ARCHIVE_MODE='.$this->archiveMode);
    }

    private function dql(array $searches = []): string
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getExpressionBuilder')->willReturn(new Expr());

        $qb = new QueryBuilder($em);
        $qb->select('obj')->from(Event::class, 'obj');

        (new EventFinder())->configureQueryBuilder($qb, $searches);

        return $qb->getDQL();
    }
}
