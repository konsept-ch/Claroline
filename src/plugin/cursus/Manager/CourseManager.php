<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CursusBundle\Manager;

use Claroline\AppBundle\API\Crud;
use Claroline\AppBundle\Manager\PlatformManager;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\Resource\Directory;
use Claroline\CoreBundle\Entity\Resource\ResourceNode;
use Claroline\CoreBundle\Entity\Organization\Organization;
use Claroline\CoreBundle\Event\GenericDataEvent;
use Claroline\CoreBundle\Manager\Template\TemplateManager;
use Claroline\CoreBundle\Manager\ResourceManager;
use Claroline\CursusBundle\Entity\Course;
use Claroline\CursusBundle\Entity\Registration\AbstractRegistration;
use Claroline\CursusBundle\Entity\Registration\CourseUser;
use Claroline\CursusBundle\Entity\Registration\SessionUser;
use Claroline\CursusBundle\Entity\Session;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CourseManager
{
    /** @var EventDispatcherInterface */
    private $eventDispatcher;
    /** @var TranslatorInterface */
    private $translator;
    /** @var ObjectManager */
    private $om;
    /** @var Crud */
    private $crud;
    /** @var PlatformManager */
    private $platformManager;
    /** @var TemplateManager */
    private $templateManager;
    /** @var SessionManager */
    private $sessionManager;
    /** @var ResourceManager */
    private $resourceManager;

    private $courseUserRepo;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        TranslatorInterface $translator,
        ObjectManager $om,
        Crud $crud,
        PlatformManager $platformManager,
        TemplateManager $templateManager,
        SessionManager $sessionManager,
        ResourceManager $resourceManager
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->om = $om;
        $this->crud = $crud;
        $this->translator = $translator;
        $this->platformManager = $platformManager;
        $this->templateManager = $templateManager;
        $this->sessionManager = $sessionManager;
        $this->resourceManager = $resourceManager;

        $this->courseUserRepo = $this->om->getRepository(CourseUser::class);
    }

    public function generateFromTemplate(Course $course, string $locale)
    {
        $placeholders = [
            'course_name' => $course->getName(),
            'course_code' => $course->getCode(),
            'course_description' => $course->getDescription(),
            'course_poster' => $course->getPoster() ? '<img src="'.$this->platformManager->getUrl().'/'.$course->getPoster().'" style="max-width: 100%;"/>' : '',
            'course_default_days' => $course->getDefaultSessionDays(),
            'course_default_hours' => $course->getDefaultSessionHours(),
            'course_public_registration' => $this->translator->trans($course->getPublicRegistration() ? 'yes' : 'no', [], 'platform'),
            'course_max_users' => $course->getMaxUsers(),
        ];

        $content = $this->templateManager->getTemplate('training_course', $placeholders, $locale);

        // append all available sessions to the export
        foreach ($course->getSessions() as $session) {
            if (!$session->isTerminated()) {
                $content .= "<div style='page-break-before: always'>{$this->sessionManager->generateFromTemplate($session, $locale)}</div>";
            }
        }

        return $content;
    }

    public function addUsers(Course $course, array $users): array
    {
        $results = [];

        $registrationDate = new \DateTime();

        $this->om->startFlushSuite();

        foreach ($users as $user) {
            $courseUser = $this->courseUserRepo->findOneBy(['course' => $course, 'user' => $user]);

            if (empty($courseUser)) {
                $courseUser = new CourseUser();
                $courseUser->setCourse($course);
                $courseUser->setUser($user);
                $courseUser->setType(AbstractRegistration::LEARNER);
                $courseUser->setDate($registrationDate);

                $this->om->persist($courseUser);

                $results[] = $courseUser;
            }
        }

        $this->om->endFlushSuite();

        return $results;
    }

    /**
     * Creates an independent course copy. Registrations and events are deliberately
     * not traversed; sessions keep their content/location associations only.
     */
    public function duplicate(Course $source, array $data): Course
    {
        $course = new Course();
        $course->setName($data['name'] ?? $source->getName().' - copy');
        $course->setDescription($data['description'] ?? $source->getDescription());
        $course->setPlainDescription($data['plainDescription'] ?? $source->getPlainDescription());
        $requestedCode = trim((string) ($data['code'] ?? ''));
        if ($requestedCode !== '') {
            if ($this->om->getRepository(Course::class)->findOneBy(['code' => $requestedCode])) {
                throw new \InvalidArgumentException('The requested course code is already used.');
            }
            $course->setCode($requestedCode);
        } else {
            $course->setCode($this->generateUniqueCourseCode($course->getName()));
        }
        $course->setWorkspace($source->getWorkspace());
        $course->setWorkspaceModel($source->getWorkspaceModel());
        $course->setPublicRegistration($source->getPublicRegistration());
        $course->setAutoRegistration($source->getAutoRegistration());
        $course->setPublicUnregistration($source->getPublicUnregistration());
        $course->setRegistrationValidation($source->getRegistrationValidation());
        $course->setRegistrationMail($source->getRegistrationMail());
        $course->setUserValidation($source->getUserValidation());
        $course->setPendingRegistrations($source->getPendingRegistrations());
        $course->setMaxUsers($source->getMaxUsers());
        $course->setPrice($source->getPrice());
        $course->setPriceDescription($source->getPriceDescription());
        $course->setPropagateRegistration($source->getPropagateRegistration());
        $course->setHideSessions($source->getHideSessions());
        $course->setSessionOpening($source->getSessionOpening());
        $course->setDefaultSessionDays($source->getDefaultSessionDays());
        $course->setDefaultSessionHours($source->getDefaultSessionHours());
        $organizations = $source->getOrganizations()->toArray();
        if (array_key_exists('organizations', $data)) {
            $organizations = [];
            foreach ((array) $data['organizations'] as $organizationId) {
                $organization = $this->om->getRepository(Organization::class)->findOneBy(['uuid' => $organizationId]);
                if ($organization) {
                    $organizations[] = $organization;
                }
            }
        }
        foreach ($organizations as $organization) {
            $course->addOrganization($organization);
        }

        $this->om->persist($course);
        $this->om->flush();
        $this->ensureResource($course);
        $this->copyTags($source, $course);

        $byId = [];
        foreach ($source->getSessions() as $session) {
            $byId[$session->getUuid()] = $session;
        }
        // When the client does not send a selection, keep all sessions.
        // This also prevents a partial/older client payload from silently
        // creating a course without sessions.
        $requestedSessions = $data['sessions'] ?? array_map(function (Session $session) {
            return [
                'id' => $session->getUuid(),
                'startDate' => $session->getStartDate() ? $session->getStartDate()->format(DATE_ATOM) : null,
                'endDate' => $session->getEndDate() ? $session->getEndDate()->format(DATE_ATOM) : null,
            ];
        }, $source->getSessions()->toArray());
        foreach ($requestedSessions as $requested) {
            $sourceSession = $byId[$requested['id'] ?? ''] ?? null;
            if (!$sourceSession) {
                continue;
            }
            $session = new Session();
            $session->setName($requested['name'] ?? $sourceSession->getName());
            $session->setCode($this->sessionManager->generateUniqueSessionCode($session->getName()));
            $session->setCourse($course);
            $session->setDescription($sourceSession->getDescription());
            $session->setPlainDescription($sourceSession->getPlainDescription());
            $session->setStartDate($this->parseDate($requested['startDate'] ?? null, $sourceSession->getStartDate()));
            $session->setEndDate($this->parseDate($requested['endDate'] ?? null, $sourceSession->getEndDate()));
            $session->setDefaultSession($sourceSession->isDefaultSession());
            $session->setLocation($sourceSession->getLocation());
            $session->setResources($sourceSession->getResources()->toArray());
            $session->setEventRegistrationType($sourceSession->getEventRegistrationType());
            $this->om->persist($session);
        }
        $this->om->flush();

        return $course;
    }

    /**
     * Copies the tags from the source course to the independent copy.
     */
    private function copyTags(Course $source, Course $copy): void
    {
        $event = new GenericDataEvent([
            'class' => Course::class,
            'ids' => [$source->getUuid()],
        ]);
        $this->eventDispatcher->dispatch($event, 'claroline_retrieve_used_tags_by_class_and_ids');

        $this->eventDispatcher->dispatch(new GenericDataEvent([
            'tags' => $event->getResponse() ?? [],
            'data' => [[
                'class' => Course::class,
                'id' => $copy->getUuid(),
                'name' => $copy->getName(),
            ]],
            'replace' => true,
        ]), 'claroline_tag_multiple_data');
    }

    private function generateUniqueCourseCode(string $name): string
    {
        $base = strtoupper(preg_replace('/[^A-Z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $name)));
        $base = trim($base, '-') ?: 'COURSE';
        do {
            $code = substr($base, 0, 48).'-'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while ($this->om->getRepository(Course::class)->findOneBy(['code' => $code]));

        return $code;
    }

    private function parseDate(?string $value, ?\DateTime $fallback): ?\DateTime
    {
        if (!$value) {
            return $fallback ? clone $fallback : null;
        }
        try {
            return new \DateTime($value);
        } catch (\Exception $e) {
            return $fallback ? clone $fallback : null;
        }
    }

    /**
     * @param CourseUser[] $courseUsers
     */
    public function removeUsers(array $courseUsers)
    {
        foreach ($courseUsers as $courseUser) {
            $this->om->remove($courseUser);
        }

        $this->om->flush();
    }

    /**
     * @param CourseUser[] $courseUsers
     */
    public function moveUsers(Session $targetSession, array $courseUsers)
    {
        $this->om->startFlushSuite();

        // unregister users from course pending list
        $this->removeUsers($courseUsers);

        // register to the new session
        $this->sessionManager->addUsers($targetSession, array_map(function (CourseUser $courseUser) {
            return $courseUser->getUser();
        }, $courseUsers), AbstractRegistration::LEARNER, true);

        $this->om->endFlushSuite();
    }

    /**
     * @param SessionUser[] $sessionUsers
     */
    public function moveToPending(Course $course, array $sessionUsers)
    {
        if (!empty($sessionUsers)) {
            $session = $sessionUsers[0]->getSession();

            if (!empty($session) && !empty($course)) {
                $this->om->startFlushSuite();

                // remove users from session
                $this->sessionManager->removeUsers($session, $sessionUsers);

                // add users to the pending list of the course
                $this->addUsers($course, array_map(function (SessionUser $sessionUser) {
                    return $sessionUser->getUser();
                }, $sessionUsers));

                $this->om->endFlushSuite();
            }
        }
    }

    /**
     * Ensures the course has a linked directory resource node.
     */
    public function ensureResource(Course $course): ?ResourceNode
    {
        if ($course->getResource()) {
            $resourceNode = $course->getResource();
            $workspace = $course->getWorkspace();

            if ($workspace && (!$resourceNode->getWorkspace() || $resourceNode->getWorkspace()->getId() !== $workspace->getId())) {
                $this->resourceManager->syncWorkspace($resourceNode, $workspace);
            }

            return $resourceNode;
        }

        $workspace = $course->getWorkspace();

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

        if ($workspace) {
            $resourceNode->setWorkspace($workspace);
        }

        $resource = $this->crud->create(Directory::class, []);
        $resource->setResourceNode($resourceNode);
        $course->setResource($resourceNode);

        $this->om->persist($resourceNode);
        $this->om->persist($resource);
        $this->om->persist($course);
        $this->om->flush();

        return $resourceNode;
    }
}
