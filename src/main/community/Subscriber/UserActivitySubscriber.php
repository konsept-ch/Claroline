<?php

namespace Claroline\CommunityBundle\Subscriber;

use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Update the user last activity date at the end of each request.
 */
class UserActivitySubscriber implements EventSubscriberInterface
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var ObjectManager */
    private $om;

    public function __construct(TokenStorageInterface $tokenStorage, ObjectManager $om)
    {
        $this->tokenStorage = $tokenStorage;
        $this->om = $om;
    }

    public static function getSubscribedEvents(): array
    {
        return [
             TerminateEvent::class => ['setLastActivityDate', -500], // arbitrary low priority to be the last
        ];
    }

    public function setLastActivityDate(TerminateEvent $event)
    {
        $currentUser = $this->tokenStorage->getToken() ? $this->tokenStorage->getToken()->getUser() : null;
        if ($currentUser instanceof User) {
            $now = new \DateTime();
            // We update the user last activity only if there is no activity in the last 30 seconds
            // to avoid too many updates in the user table.
            if (empty($currentUser->getLastActivity()) || $now > date_add($currentUser->getLastActivity(), new \DateInterval('PT30S'))) {
                $currentUser->setLastActivity($now);

                if ($event->getResponse()->getStatusCode() >= 400) {
                    // la requête a échoué : l'unité de travail peut contenir des objets à moitié
                    // préparés (ex. une réservation refusée pour salle occupée). Un flush() global
                    // les écrirait. On ne met à jour que la date d'activité.
                    $this->om
                        ->createQuery('UPDATE Claroline\CoreBundle\Entity\User u SET u.lastActivity = :now WHERE u.id = :id')
                        ->setParameters(['now' => $now, 'id' => $currentUser->getId()])
                        ->execute();

                    return;
                }

                $this->om->persist($currentUser);
                $this->om->flush();
            }
        }
    }
}
