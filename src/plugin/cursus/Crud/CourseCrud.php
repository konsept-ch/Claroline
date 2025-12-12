<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CursusBundle\Crud;

use Claroline\AppBundle\API\Crud;
use Claroline\AppBundle\Event\Crud\CreateEvent;
use Claroline\AppBundle\Event\Crud\DeleteEvent;
use Claroline\AppBundle\Event\Crud\UpdateEvent;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\Organization\Organization;
use Claroline\CoreBundle\Entity\Resource\Directory;
use Claroline\CoreBundle\Entity\Resource\ResourceNode;
use Claroline\CoreBundle\Entity\User;
use Claroline\CursusBundle\Entity\Course;
use Claroline\CursusBundle\Event\Log\LogCourseCreateEvent;
use Claroline\CursusBundle\Event\Log\LogCourseDeleteEvent;
use Claroline\CursusBundle\Event\Log\LogCourseEditEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CourseCrud
{
    /** @var TokenStorageInterface */
    private $tokenStorage;
    /** @var EventDispatcherInterface */
    private $eventDispatcher;
    /** @var ObjectManager */
    private $om;
    /** @var Crud */
    private $crud;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        TokenStorageInterface $tokenStorage,
        ObjectManager $om,
        Crud $crud
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->tokenStorage = $tokenStorage;
        $this->om = $om;
        $this->crud = $crud;
    }

    public function preCreate(CreateEvent $event)
    {
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        /** @var Course $course */
        $course = $event->getObject();

        // If Course is associated to no organization, initializes it with organizations administrated by authenticated user
        // or at last resort with default organizations
        if ($course->getOrganizations()->isEmpty()) {
            if ($user instanceof User && !empty($user->getMainOrganization())) {
                $course->addOrganization($user->getMainOrganization());
            } else {
                // Initializes Course with default organizations if no others organization is found
                /** @var Organization[] $defaultOrganizations */
                $defaultOrganizations = $this->om->getRepository(Organization::class)->findBy(['default' => true]);

                foreach ($defaultOrganizations as $organization) {
                    $course->addOrganization($organization);
                }
            }
        }

        $resourceNode = $this->crud->create(ResourceNode::class, [
            'name' => $course->getName(),
            'meta' => [
                'published' => true,
                'type' => 'directory',
            ],
            'rights' => [[
                'permissions' => [
                    'open' => true,
                    'edit' => true,
                    'delete' => false,
                    'administrate' => true,
                    'export' => false,
                    'copy' => false
                ],
                'name' => 'ROLE_ADMIN',
                'translationKey' => 'admin',
            ], [
                // allow authenticated users (incl. tutors) to browse the folder; edits stay restricted
                'permissions' => [
                    'open' => true,
                    'edit' => false,
                    'delete' => false,
                    'administrate' => false,
                    'export' => false,
                    'copy' => false
                ],
                'name' => 'ROLE_USER',
                'translationKey' => 'user',
            ]],
        ]);

        $resource = $this->crud->create(Directory::class, []);
        $resource->setResourceNode($resourceNode);
        $course->setResource($resourceNode);

        $this->om->persist($resourceNode);
        $this->om->persist($resource);

        $course->setCreatedAt(new \DateTime());
        $course->setUpdatedAt(new \DateTime());

        if (empty($course->getCreator()) && $user instanceof User) {
            $course->setCreator($user);
        }
    }

    public function postCreate(CreateEvent $event)
    {
        $event = new LogCourseCreateEvent($event->getObject());
        $this->eventDispatcher->dispatch($event, 'log');
    }

    public function preUpdate(UpdateEvent $event)
    {
        /** @var Course $course */
        $course = $event->getObject();

        $course->setUpdatedAt(new \DateTime());
    }

    public function postUpdate(UpdateEvent $event)
    {
        $event = new LogCourseEditEvent($event->getObject());
        $this->eventDispatcher->dispatch($event, 'log');
    }

    public function preDelete(DeleteEvent $event)
    {
        $event = new LogCourseDeleteEvent($event->getObject());
        $this->eventDispatcher->dispatch($event, 'log');
    }
}
