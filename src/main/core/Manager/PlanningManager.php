<?php

namespace Claroline\CoreBundle\Manager;

use Claroline\AppBundle\Entity\IdentifiableInterface;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\Planning\AbstractPlanned;
use Claroline\CoreBundle\Entity\Planning\Planning;
use Claroline\CoreBundle\Event\Planning\PlanObjectEvent;
use Claroline\CoreBundle\Event\Planning\UnplanObjectEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PlanningManager
{
    /** @var EventDispatcherInterface */
    private $eventDispatcher;
    /** @var ObjectManager */
    private $om;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ObjectManager $om
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->om = $om;
    }

    public function addToPlanning(AbstractPlanned $planned, IdentifiableInterface $object)
    {
        // contrôle d'abord (ex. salle occupée), avant de toucher à l'unité de travail : si un
        // subscriber refuse, il ne doit rester aucun objet persisté qu'un flush ultérieur de la
        // même requête pourrait écrire
        $this->eventDispatcher->dispatch(new PlanObjectEvent($planned, $object));

        $planning = $this->om->getRepository(Planning::class)->findOneBy([
            'objectId' => $object->getUuid(),
        ]);

        if (empty($planning)) {
            $planning = new Planning();
            $planning->setObjectClass(get_class($object));
            $planning->setObjectId($object->getUuid());

            $this->om->persist($planning);
        }

        $planning->addPlannedObject($planned->getPlannedObject());
        $this->om->persist($planned);

        $this->om->flush();
    }

    public function removeFromPlanning(AbstractPlanned $planned, IdentifiableInterface $object)
    {
        $planning = $this->om->getRepository(Planning::class)->findOneBy([
            'objectId' => $object->getUuid(),
        ]);

        if ($planning) {
            $planning->removePlannedObject($planned->getPlannedObject());
        }

        $this->eventDispatcher->dispatch(new UnplanObjectEvent($planned, $object));

        $this->om->flush();
    }
}
