<?php

namespace Claroline\CoreBundle\Tests\Unit\Library\ICS;

use Claroline\CoreBundle\Library\ICS\ICSGenerator;
use Claroline\CoreBundle\Library\Normalizer\DateNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Le fichier .ics d'une séance est nommé d'après son uuid et réécrit à chaque invitation.
 *
 * Constaté sur VAL le 16.11.2026 : le fichier était complété au lieu d'être remplacé, et chaque
 * invitation joignait toutes les précédentes, collées sans saut de ligne
 * (« END:VCALENDARBEGIN:VCALENDAR »). Correction reprise de l'amont (Claroline 14.1).
 */
class ICSGeneratorTest extends TestCase
{
    private $filesDir;

    public function testCreateFileReplacesPreviousInvitationForSameEvent()
    {
        $generator = new ICSGenerator($this->filesDir);

        $first = $generator->createFile($this->props('2027-09-08 06:30:00', '2027-09-08 15:00:00'), 'event-uuid');
        $second = $generator->createFile($this->props('2027-09-15 06:30:00', '2027-09-15 15:00:00'), 'event-uuid');

        $this->assertSame($first, $second);

        $content = file_get_contents($second);
        $this->assertSame(1, substr_count($content, 'BEGIN:VCALENDAR'));
        $this->assertSame(1, substr_count($content, 'END:VCALENDAR'));
        // seule la dernière invitation est jointe
        $this->assertStringContainsString('DTSTART:20270915T063000Z', $content);
        $this->assertStringNotContainsString('DTSTART:20270908T063000Z', $content);
    }

    protected function setUp(): void
    {
        $this->filesDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ics-test-'.uniqid();
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->filesDir);
    }

    private function props(string $start, string $end): array
    {
        return [
            'summary' => 'Entretien de collaboration | 2027 | Session 3',
            'description' => '',
            'location' => 'CEP',
            // même format que Claroline\CursusBundle\Manager\EventManager::getICS()
            'dtstart' => DateNormalizer::normalize(new \DateTime($start, new \DateTimeZone('UTC'))),
            'dtend' => DateNormalizer::normalize(new \DateTime($end, new \DateTimeZone('UTC'))),
            'url' => null,
        ];
    }
}
