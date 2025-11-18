<?php

// Minimal Cursus seed: one course, one session, one event.

use Claroline\KernelBundle\Kernel;
use Claroline\CoreBundle\Entity\User;
use Claroline\CursusBundle\Entity\Course;
use Claroline\CursusBundle\Entity\Session as CursusSession;
use Claroline\CursusBundle\Entity\Event as CursusEvent;

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/../config/bootstrap.php';

$env = $_ENV['APP_ENV'] ?? 'dev';
$debug = (bool)($_ENV['APP_DEBUG'] ?? ('prod' !== $env));

$kernel = new Kernel($env, $debug);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

// Pick a creator user (demo1 if present, otherwise the first user)
$userRepo = $em->getRepository(User::class);
$creator = $userRepo->findOneBy(['username' => 'demo1']);
if (!$creator) {
    $creator = $userRepo->findOneBy([]);
}

if (!$creator) {
    fwrite(STDERR, "No user found to assign as creator. Seed aborted.\n");
    exit(1);
}

$courseCode = 'DEMO-COURSE-001';
$existingCourse = $em->getRepository(Course::class)->findOneBy(['code' => $courseCode]);
if ($existingCourse) {
    echo "Course already exists: {$courseCode}\n";
    $course = $existingCourse;
} else {
    $course = new Course();
    $course->setCode($courseCode);
    $course->setName('Demo Training');
    $course->setCreator($creator);
    $course->setPublicRegistration(true);
    $em->persist($course);
}

$sessionCode = 'DEMO-COURSE-001-S1';
$existingSession = $em->getRepository(CursusSession::class)->findOneBy(['code' => $sessionCode]);
if ($existingSession) {
    echo "Session already exists: {$sessionCode}\n";
    $session = $existingSession;
} else {
    $session = new CursusSession();
    $session->setCourse($course);
    $session->setCode($sessionCode);
    $session->setName('Demo Session 1');
    $session->setCreator($creator);
    $session->setStartDate((new \DateTime('now'))->modify('+3 days')->setTime(9, 0));
    $session->setEndDate((new \DateTime('now'))->modify('+3 days')->setTime(17, 0));
    $em->persist($session);
}

// Add a first planned event inside the session timeframe
$eventCode = $sessionCode.'-E1';
$eventExists = $em->getRepository(CursusEvent::class)->findOneBy(['code' => $eventCode]);
if (!$eventExists) {
    $event = new CursusEvent();
    $event->setSession($session);
    $event->setCode($eventCode);
    // PlannedObject metadata (name, start, end) are proxied; use magic accessors
    $event->setName('Kickoff');
    $event->setStartDate((new \DateTime('now'))->modify('+3 days')->setTime(10, 0));
    $event->setEndDate((new \DateTime('now'))->modify('+3 days')->setTime(12, 0));
    $event->setCreator($creator);
    $em->persist($event);
}

$em->flush();

echo "Seeded:\n - Course: {$course->getCode()} / {$course->getName()}\n - Session: {$session->getCode()} / {$session->getName()}\n - Event: {$eventCode} Kickoff (in session)\n";
