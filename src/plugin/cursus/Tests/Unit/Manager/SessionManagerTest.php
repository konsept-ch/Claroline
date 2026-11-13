<?php

namespace Claroline\CursusBundle\Tests\Unit\Manager;

use Claroline\AppBundle\API\Crud;
use Claroline\AppBundle\Event\StrictDispatcher;
use Claroline\AppBundle\Manager\PlatformManager;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\Organization\Organization;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Library\ICS\ICSGenerator;
use Claroline\CoreBundle\Library\RoutingHelper;
use Claroline\CoreBundle\Library\Testing\MockeryTestCase;
use Claroline\CoreBundle\Manager\LocaleManager;
use Claroline\CoreBundle\Manager\MailManager;
use Claroline\CoreBundle\Manager\PlanningManager;
use Claroline\CoreBundle\Manager\RoleManager;
use Claroline\CoreBundle\Manager\Template\TemplateManager;
use Claroline\CoreBundle\Manager\Workspace\WorkspaceManager;
use Claroline\CursusBundle\Entity\Course;
use Claroline\CursusBundle\Entity\Event;
use Claroline\CursusBundle\Entity\Session;
use Claroline\CursusBundle\Manager\EventManager;
use Claroline\CursusBundle\Manager\EventPresenceManager;
use Claroline\CursusBundle\Manager\SessionManager;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Rejoue l'incident du 13.11.2026 (ENT_LS/27/03) : une session dont l'une des séances n'a pas
 * de date. Inscrire un participant ne doit plus planter, et il doit rester inscrit à toutes
 * les séances, y compris celle dont la date n'est pas encore fixée.
 */
class SessionManagerTest extends MockeryTestCase
{
    private $templateManager;
    private $planningManager;
    private $ics;
    private $strictDispatcher;
    private $placeholders = [];

    public function testRegistrationSucceedsWhenOneEventHasNoDates()
    {
        $dated = $this->event('Session 3 - Jour 1', new \DateTime('2027-09-08 06:30:00'), new \DateTime('2027-09-08 15:00:00'));
        $undated = $this->event('Session 4 - Jour 1', null, null);
        $session = $this->session(new \DateTime('2027-09-08'), new \DateTime('2027-09-15'), [$dated, $undated]);
        $user = $this->user();

        // le participant est inscrit aux deux séances : la séance sans date garde ses inscrits
        $this->planningManager->shouldReceive('addToPlanning')->once()->with($dated, $user);
        $this->planningManager->shouldReceive('addToPlanning')->once()->with($undated, $user);
        // une seule invitation de séance, pour la séance datée
        $this->ics->shouldReceive('createFile')->once()->andReturn('/tmp/invitation.ics');
        $this->strictDispatcher->shouldReceive('dispatch')->once();

        $sessionUsers = $this->manager()->addUsers($session, [$user]);

        $this->assertCount(1, $sessionUsers);
        $this->assertTrue($sessionUsers[0]->isValidated());
        $this->assertTrue($sessionUsers[0]->isConfirmed());
    }

    public function testRegistrationSucceedsWhenSessionHasNoDates()
    {
        $session = $this->session(null, null, []);

        $sessionUsers = $this->manager()->addUsers($session, [$this->user()]);

        $this->assertCount(1, $sessionUsers);
        // e-mails de session envoyés avec une date vide plutôt que de faire échouer l'inscription
        $this->assertSame('', $this->placeholders['training_session_invitation']['session_start']);
        $this->assertSame('', $this->placeholders['training_session_invitation']['session_end']);
        $this->assertSame('', $this->placeholders['training_quota_subscription_created']['session_start']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->templateManager = $this->mock(TemplateManager::class);
        $this->templateManager->shouldReceive('getTemplate')->andReturnUsing(function ($name, $values) {
            $this->placeholders[$name] = $values;

            return 'template';
        });
        $this->planningManager = $this->mock(PlanningManager::class);
        $this->ics = $this->mock(ICSGenerator::class);
        $this->strictDispatcher = $this->mock(StrictDispatcher::class);
    }

    private function manager(): SessionManager
    {
        $repository = \Mockery::mock();
        $repository->shouldReceive('findBy')->andReturn([]);
        $repository->shouldReceive('findOneBy')->andReturn(null);

        $om = $this->mock(ObjectManager::class);
        $om->shouldReceive('getRepository')->andReturn($repository);
        $om->shouldReceive('startFlushSuite', 'endFlushSuite', 'persist');

        $eventDispatcher = $this->mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatch');

        $router = $this->mock(UrlGeneratorInterface::class);
        $router->shouldReceive('generate')->andReturn('http://localhost/confirm');

        $routingHelper = $this->mock(RoutingHelper::class);
        $routingHelper->shouldReceive('desktopUrl')->andReturn('http://localhost/#/desktop/trainings');

        $localeManager = $this->mock(LocaleManager::class);
        $localeManager->shouldReceive('getLocale')->andReturn('fr');

        $mailManager = $this->mock(MailManager::class);
        $mailManager->shouldReceive('send');

        // EventManager réel : c'est lui qui portait le plantage
        $eventManager = new EventManager(
            $eventDispatcher,
            $om,
            $router,
            $this->ics,
            $this->templateManager,
            $this->mock(TokenStorageInterface::class),
            $this->strictDispatcher,
            $this->planningManager,
            $this->mock(EventPresenceManager::class)
        );

        return new SessionManager(
            $eventDispatcher,
            $this->mock(TranslatorInterface::class),
            $om,
            $router,
            $this->mock(Crud::class),
            $this->mock(PlatformManager::class),
            $this->mock(RoleManager::class),
            $routingHelper,
            $this->templateManager,
            $this->mock(WorkspaceManager::class),
            $eventManager,
            $mailManager,
            $localeManager
        );
    }

    private function session(?\DateTime $start, ?\DateTime $end, array $events): Session
    {
        $course = new Course();
        $course->setName('Entretien de collaboration');
        $course->setCode('ENT_LS');

        $session = new Session();
        $session->setCourse($course);
        $session->setName('Session 3');
        $session->setCode('ENT_LS/27/03');
        $session->setStartDate($start);
        $session->setEndDate($end);
        // réglages de la session ENT_LS/27/03 en PROD
        $session->setRegistrationValidation(false);
        $session->setUserValidation(false);
        $session->setRegistrationMail(true);

        foreach ($events as $event) {
            $event->setSession($session);
            $session->getEvents()->add($event);
        }

        return $session;
    }

    private function event(string $name, ?\DateTime $start, ?\DateTime $end): Event
    {
        $event = new Event();
        // en base, Doctrine charge l'objet planifié ; ici il faut le créer explicitement
        $event->getPlannedObject();
        $event->setName($name);
        $event->setCode($name);
        if ($start) {
            $event->setStartDate($start);
        }
        if ($end) {
            $event->setEndDate($end);
        }

        return $event;
    }

    private function user(): User
    {
        $organization = $this->mock(Organization::class);
        $organization->shouldReceive('getAdministrators')->andReturn(new ArrayCollection());

        $user = \Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('getMainOrganization')->andReturn($organization);
        $user->shouldReceive('getUuid')->andReturn('user-uuid');

        return $user;
    }
}
