<?php

namespace Claroline\CoreBundle\Subscriber\Crud\Planning;

use Claroline\AppBundle\API\Crud;
use Claroline\AppBundle\Event\Crud\CreateEvent;
use Claroline\AppBundle\Event\Crud\DeleteEvent;
use Claroline\AppBundle\Event\Crud\UpdateEvent;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\Location\Location;
use Claroline\CoreBundle\Entity\Location\Room;
use Claroline\CoreBundle\Entity\Planning\AbstractPlanned;
use Claroline\CoreBundle\Entity\Planning\PlannedObject;
use Claroline\CoreBundle\Entity\Planning\Planning;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Library\Normalizer\DateNormalizer;
use Claroline\CoreBundle\Manager\FileManager;
use Claroline\CoreBundle\Manager\PlanningManager;
use Claroline\CoreBundle\Validator\Exception\InvalidDataException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

abstract class AbstractPlannedSubscriber implements EventSubscriberInterface
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;
    /** @var ObjectManager */
    protected $om;
    /** @var FileManager */
    protected $fileManager;
    /** @var PlanningManager */
    protected $planningManager;

    public function setTokenStorage(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function setObjectManager(ObjectManager $om)
    {
        $this->om = $om;
    }

    public function setFileManager(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    public function setPlanningManager(PlanningManager $planningManager)
    {
        $this->planningManager = $planningManager;
    }

    /**
     * Return the FQCN of the planned object (aka the name of the Entity extending AbstractPlanned).
     */
    abstract public static function getPlannedClass(): string;

    public static function getSubscribedEvents(): array
    {
        return [
            Crud::getEventName('create', 'pre', static::getPlannedClass()) => 'preCreate',
            Crud::getEventName('update', 'pre', static::getPlannedClass()) => 'preUpdate',
            Crud::getEventName('delete', 'post', static::getPlannedClass()) => 'postDelete',
        ];
    }

    public function preCreate(CreateEvent $event)
    {
        /** @var AbstractPlanned $object */
        $object = $event->getObject();

        $user = $this->tokenStorage->getToken()->getUser();
        if ($user instanceof User && empty($object->getCreator())) {
            $object->setCreator($user);
        }

        $object->setCreatedAt(new \DateTime());
        $object->setUpdatedAt(new \DateTime());

        // la salle d'abord : addToPlanning() flushe à chaque appel, et c'est le planning
        // de la salle qui peut refuser l'objet (RoomPlanningSubscriber). Traité après le
        // lieu, l'objet était déjà écrit en base quand l'erreur remontait à l'utilisateur.
        if (!empty($object->getRoom())) {
            $this->planningManager->addToPlanning($object, $object->getRoom());
        }

        if (!empty($object->getLocation())) {
            $this->planningManager->addToPlanning($object, $object->getLocation());
        }
    }

    public function preUpdate(UpdateEvent $event)
    {
        /** @var AbstractPlanned $object */
        $object = $event->getObject();
        $oldData = $event->getOldData();

        $object->setUpdatedAt(new \DateTime());

        // la salle d'abord, pour la même raison qu'à la création
        $oldRoom = !empty($oldData['room']) ? $oldData['room']['id'] : null;
        $newRoom = !empty($object->getRoom()) ? $object->getRoom()->getUuid() : null;
        if ($newRoom && $oldRoom === $newRoom && $this->datesChanged($object, $oldData)) {
            // même salle, nouvelles dates : addToPlanning() n'est pas rappelé, il faut donc
            // recontrôler ici, sans compter l'objet lui-même (il est déjà dans le planning)
            $this->checkRoomAvailability($object);
        }

        if ($oldRoom !== $newRoom) {
            // add to new room
            if ($newRoom) {
                $this->planningManager->addToPlanning($object, $object->getRoom());
            }

            // remove from old room
            if ($oldRoom) {
                /** @var Room $old */
                $old = $this->om->getObject($oldData['room'], Room::class);
                if ($old) {
                    $this->planningManager->removeFromPlanning($object, $old);
                }
            }
        }

        $oldLocation = !empty($oldData['location']) ? $oldData['location']['id'] : null;
        $newLocation = !empty($object->getLocation()) ? $object->getLocation()->getUuid() : null;
        if ($oldLocation !== $newLocation) {
            // add to new location
            if ($newLocation) {
                $this->planningManager->addToPlanning($object, $object->getLocation());
            }

            // remove from old location
            if ($oldLocation) {
                /** @var Location $old */
                $old = $this->om->getObject($oldData['location'], Location::class);
                if ($old) {
                    $this->planningManager->removeFromPlanning($object, $old);
                }
            }
        }
    }

    private function datesChanged(AbstractPlanned $object, array $oldData): bool
    {
        $newStart = $object->getStartDate() ? DateNormalizer::normalize($object->getStartDate()) : null;
        $newEnd = $object->getEndDate() ? DateNormalizer::normalize($object->getEndDate()) : null;

        return ($oldData['start'] ?? null) !== $newStart || ($oldData['end'] ?? null) !== $newEnd;
    }

    private function checkRoomAvailability(AbstractPlanned $object)
    {
        $start = $object->getStartDate();
        $end = $object->getEndDate();
        if (empty($start) || empty($end)) {
            throw new InvalidDataException('valid_end_date_required', [['path' => 'end', 'message' => 'valid_end_date_required']]);
        }

        if ($end < $start) {
            throw new InvalidDataException('invalid_date_range', [['path' => 'end', 'message' => 'invalid_date_range']]);
        }

        $available = $this->om->getRepository(Planning::class)->areDatesAvailable($object->getRoom()->getUuid(), $start, $end, $object->getUuid());
        if (!$available) {
            throw new InvalidDataException('The room is not available for this dates.', [['path' => 'room', 'message' => 'The room is not available for this dates.']]);
        }
    }

    public function postDelete(DeleteEvent $event)
    {
        /** @var AbstractPlanned $object */
        $object = $event->getObject();

        if ($object->getPoster()) {
            $this->fileManager->unlinkFile(PlannedObject::class, $object->getUuid(), $object->getPoster());
        }

        if ($object->getThumbnail()) {
            $this->fileManager->unlinkFile(PlannedObject::class, $object->getUuid(), $object->getThumbnail());
        }
    }
}
