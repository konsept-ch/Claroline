<?php

namespace Claroline\CursusBundle\Tests\Unit\Manager;

use Claroline\AppBundle\Event\StrictDispatcher;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Event\CatalogEvents\MessageEvents;
use Claroline\CoreBundle\Event\SendMessageEvent;
use Claroline\CoreBundle\Library\ICS\ICSGenerator;
use Claroline\CoreBundle\Library\Testing\MockeryTestCase;
use Claroline\CoreBundle\Manager\PlanningManager;
use Claroline\CoreBundle\Manager\Template\TemplateManager;
use Claroline\CursusBundle\Entity\Course;
use Claroline\CursusBundle\Entity\Event;
use Claroline\CursusBundle\Entity\Session;
use Claroline\CursusBundle\Manager\EventManager;
use Claroline\CursusBundle\Manager\EventPresenceManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Une séance sans date ne doit pas faire échouer l'inscription à sa session.
 *
 * Incident du 13.11.2026 : la séance 9400, sans date, rattachée à ENT_LS/27/03, faisait
 * planter toute inscription à la session (« setTimezone() on null » dans l'invitation).
 */
class EventManagerTest extends MockeryTestCase
{
    private $ics;
    private $templateManager;
    private $dispatcher;

    public function testNoInvitationForEventWithoutDates()
    {
        $this->ics->shouldNotReceive('createFile');
        $this->templateManager->shouldNotReceive('getTemplate');
        $this->dispatcher->shouldNotReceive('dispatch');

        $this->manager()->sendSessionEventInvitation($this->event(null, null), [new User()]);

        // atteindre cette ligne signifie : pas d'erreur, et aucun shouldNotReceive déclenché
        $this->addToAssertionCount(1);
    }

    public function testNoInvitationForEventWithEndDateOnly()
    {
        $this->ics->shouldNotReceive('createFile');
        $this->templateManager->shouldNotReceive('getTemplate');
        $this->dispatcher->shouldNotReceive('dispatch');

        $this->manager()->sendSessionEventInvitation(
            $this->event(null, new \DateTime('2027-02-12 16:00:00')),
            [new User()]
        );

        $this->addToAssertionCount(1);
    }

    public function testInvitationStillSentForDatedEvent()
    {
        $placeholders = null;

        $this->ics->shouldReceive('createFile')->once()->andReturn('/tmp/invitation.ics');
        $this->templateManager->shouldReceive('getTemplate')
            ->twice()
            ->andReturnUsing(function ($name, $values) use (&$placeholders) {
                $placeholders = $values;

                return 'template';
            });
        $this->dispatcher->shouldReceive('dispatch')
            ->once()
            ->with(MessageEvents::MESSAGE_SENDING, SendMessageEvent::class, \Mockery::type('array'));

        $this->manager()->sendSessionEventInvitation(
            $this->event(new \DateTime('2027-09-08 06:30:00'), new \DateTime('2027-09-08 15:00:00')),
            [new User()]
        );

        $this->assertSame('08/09/2027 08:30', $placeholders['event_start']);
        // la session de l'exemple n'a pas de dates : le modèle reçoit une valeur vide au lieu de planter
        $this->assertSame('', $placeholders['session_start']);
        $this->assertSame('', $placeholders['session_end']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->ics = $this->mock(ICSGenerator::class);
        $this->templateManager = $this->mock(TemplateManager::class);
        $this->dispatcher = $this->mock(StrictDispatcher::class);
    }

    private function manager(): EventManager
    {
        $repository = \Mockery::mock();
        $repository->shouldReceive('findBy')->andReturn([]);

        $om = $this->mock(ObjectManager::class);
        $om->shouldReceive('getRepository')->andReturn($repository);

        return new EventManager(
            $this->mock(EventDispatcherInterface::class),
            $om,
            $this->mock(UrlGeneratorInterface::class),
            $this->ics,
            $this->templateManager,
            $this->mock(TokenStorageInterface::class),
            $this->dispatcher,
            $this->mock(PlanningManager::class),
            $this->mock(EventPresenceManager::class)
        );
    }

    private function event(?\DateTime $start, ?\DateTime $end): Event
    {
        $course = new Course();
        $course->setName('Entretien de collaboration');
        $course->setCode('ENT_LS');

        $session = new Session();
        $session->setCourse($course);
        $session->setName('Session 3');
        $session->setCode('ENT_LS/27/03');

        $event = new Event();
        // en base, Doctrine charge l'objet planifié ; ici il faut le créer explicitement
        $event->getPlannedObject();
        $event->setSession($session);
        $event->setName('Session 4 - Jour 1');
        $event->setCode('ENT_LS/27/04');
        if ($start) {
            $event->setStartDate($start);
        }
        if ($end) {
            $event->setEndDate($end);
        }

        return $event;
    }
}
