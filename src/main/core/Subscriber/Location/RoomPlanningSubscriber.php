<?php

namespace Claroline\CoreBundle\Subscriber\Location;

use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\Location\RoomBooking;
use Claroline\CoreBundle\Entity\Location\Room;
use Claroline\CoreBundle\Entity\Planning\Planning;
use Claroline\CoreBundle\Event\Planning\PlanObjectEvent;
use Claroline\CoreBundle\Event\Planning\UnplanObjectEvent;
use Claroline\CoreBundle\Validator\Exception\InvalidDataException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RoomPlanningSubscriber implements EventSubscriberInterface
{
    /** @var ObjectManager */
    private $om;

    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
    }

    public static function getSubscribedEvents()
    {
        return [
            PlanObjectEvent::class => 'onPlan',
            UnplanObjectEvent::class => 'onUnplan',
        ];
    }

    public function onPlan(PlanObjectEvent $event)
    {
        $object = $event->getObject();
        if ($object instanceof Room) {
            $planned = $event->getPlanned();

            // une réservation de salle doit porter deux dates dans le bon ordre :
            // sans fin, le contrôle de disponibilité ne peut pas être fait
            if (empty($planned->getStartDate())) {
                throw new InvalidDataException('valid_start_date_required', [['path' => 'start', 'message' => 'valid_start_date_required']]);
            }

            if (empty($planned->getEndDate())) {
                throw new InvalidDataException('valid_end_date_required', [['path' => 'end', 'message' => 'valid_end_date_required']]);
            }

            if ($planned->getEndDate() < $planned->getStartDate()) {
                throw new InvalidDataException('invalid_date_range', [['path' => 'end', 'message' => 'invalid_date_range']]);
            }

            // check if the room is available
            $available = $this->om->getRepository(Planning::class)->areDatesAvailable($object->getUuid(), $planned->getStartDate(), $planned->getEndDate());
            if (!$available) {
                throw new InvalidDataException('The room is not available for this dates.', [['path' => 'room', 'message' => 'The room is not available for this dates.']]);
            }

            // create a booking for the room
            $booking = new RoomBooking();
            $booking->setRoom($object);
            $booking->setStartDate($planned->getStartDate());
            $booking->setEndDate($planned->getEndDate());
        }
    }

    public function onUnplan(UnplanObjectEvent $event)
    {
        $object = $event->getObject();
        if ($object instanceof Room) {
            $planned = $event->getPlanned();

            $booking = $this->om->getRepository(RoomBooking::class)->findOneBy([
                'room' => $object,
                'startDate' => $planned->getStartDate(),
            ]);

            if ($booking) {
                $this->om->remove($booking);
                $this->om->flush();
            }
        }
    }
}
