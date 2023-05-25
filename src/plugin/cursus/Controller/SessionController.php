<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CursusBundle\Controller;

use Claroline\AppBundle\Controller\AbstractCrudController;
use Claroline\AppBundle\Manager\PdfManager;
use Claroline\CoreBundle\Entity\Group;
use Claroline\CoreBundle\Entity\Organization\Organization;
use Claroline\CoreBundle\Entity\Tool\Tool;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Library\Normalizer\TextNormalizer;
use Claroline\CoreBundle\Library\RoutingHelper;
use Claroline\CoreBundle\Manager\Tool\ToolManager;
use Claroline\CoreBundle\Security\PermissionCheckerTrait;
use Claroline\CursusBundle\Entity\Course;
use Claroline\CursusBundle\Entity\Event;
use Claroline\CursusBundle\Entity\Registration\AbstractRegistration;
use Claroline\CursusBundle\Entity\Registration\SessionCancellation;
use Claroline\CursusBundle\Entity\Registration\SessionGroup;
use Claroline\CursusBundle\Entity\Registration\SessionUser;
use Claroline\CursusBundle\Entity\Session;
use Claroline\CursusBundle\Manager\SessionManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/cursus_session")
 */
class SessionController extends AbstractCrudController
{
    use PermissionCheckerTrait;

    private TokenStorageInterface $tokenStorage;
    private TranslatorInterface $translator;
    private RoutingHelper $routingHelper;
    private ToolManager $toolManager;
    private SessionManager $manager;
    private PdfManager $pdfManager;

    public function __construct(
        AuthorizationCheckerInterface $authorization,
        TokenStorageInterface $tokenStorage,
        TranslatorInterface $translator,
        RoutingHelper $routingHelper,
        ToolManager $toolManager,
        SessionManager $manager,
        PdfManager $pdfManager
    ) {
        $this->authorization = $authorization;
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
        $this->routingHelper = $routingHelper;
        $this->toolManager = $toolManager;
        $this->manager = $manager;
        $this->pdfManager = $pdfManager;
    }

    public function getName(): string
    {
        return 'cursus_session';
    }

    public function getClass(): string
    {
        return Session::class;
    }

    protected function getDefaultHiddenFilters(): array
    {
        $filters = [];
        if (!$this->authorization->isGranted('ROLE_ADMIN')) {
            /** @var User $user */
            $user = $this->tokenStorage->getToken()->getUser();

            // filter by organization
            $organizations = [];
            if ($user instanceof User) {
                $organizations = $user->getOrganizations();
            }

            $filters['organizations'] = array_map(function (Organization $organization) {
                return $organization->getUuid();
            }, $organizations);

            // hide hidden sessions for non admin
            if (!$this->checkToolAccess('EDIT')) {
                $filters['hidden'] = false;
            }
        }

        return $filters;
    }

    /**
     * @Route("/public", name="apiv2_cursus_session_public", methods={"GET"})
     */
    public function listPublicAction(Request $request): JsonResponse
    {
        $options = static::getOptions();
        $params = $request->query->all();

        $params['hiddenFilters'] = $this->getDefaultHiddenFilters();
        $params['hiddenFilters']['publicRegistration'] = true;
        $params['hiddenFilters']['terminated'] = false;

        // hide hidden sessions for non admin
        if (!$this->checkToolAccess('EDIT')) {
            $params['hiddenFilters']['hidden'] = false;
        }

        return new JsonResponse(
            $this->finder->search(Session::class, $params, $options['list'] ?? [])
        );
    }

    /**
     * @Route("/{id}/pdf", name="apiv2_cursus_session_download_pdf", methods={"GET"})
     * @EXT\ParamConverter("session", class="Claroline\CursusBundle\Entity\Session", options={"mapping": {"id": "uuid"}})
     */
    public function downloadPdfAction(Session $session, Request $request): StreamedResponse
    {
        $this->checkPermission('OPEN', $session, [], true);

        return new StreamedResponse(function () use ($session, $request) {
            echo $this->pdfManager->fromHtml(
                $this->manager->generateFromTemplate($session, $request->getLocale())
            );
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename='.TextNormalizer::toKey($session->getName()).'.pdf',
        ]);
    }

    /**
     * @Route("/{id}/events", name="apiv2_cursus_session_list_events")
     * @EXT\ParamConverter("session", class="Claroline\CursusBundle\Entity\Session", options={"mapping": {"id": "uuid"}})
     */
    public function listEventsAction(Session $session, Request $request): JsonResponse
    {
        $this->checkPermission('OPEN', $session, [], true);

        $params = $request->query->all();
        $params['hiddenFilters'] = $this->getDefaultHiddenFilters();
        $params['hiddenFilters']['session'] = $session->getUuid();

        return new JsonResponse(
            $this->finder->search(Event::class, $params)
        );
    }

    /**
     * @Route("/{id}/users/{type}", name="apiv2_cursus_session_list_users", methods={"GET"})
     * @EXT\ParamConverter("session", class="Claroline\CursusBundle\Entity\Session", options={"mapping": {"id": "uuid"}})
     */
    public function listUsersAction(Session $session, string $type, Request $request): JsonResponse
    {
        $this->checkPermission('OPEN', $session, [], true);

        $params = $request->query->all();
        if (!isset($params['hiddenFilters'])) {
            $params['hiddenFilters'] = [];
        }
        $params['hiddenFilters']['session'] = $session->getUuid();
        $params['hiddenFilters']['type'] = $type;
        $params['hiddenFilters']['pending'] = false;

        // only list participants of the same organization
        if (SessionUser::LEARNER === $type && !$this->authorization->isGranted('ROLE_ADMIN')) {
            /** @var User $user */
            $user = $this->tokenStorage->getToken()->getUser();
            $organizations = null;

            // filter by organizations
            if ($user instanceof User) {
                $registrations = $this->om->getRepository(SessionUser::class)->findByUser($session, $user);
                if (0 == count($registrations)) {
                    return new JsonResponse($this->finder->formatPaginatedData([], 0, 0, -1, [], []));
                }

                if (!$this->isTutor($registrations)) {
                    $organizations = $user->getOrganizations();
                }
            } else {
                return new JsonResponse($this->finder->formatPaginatedData([], 0, 0, -1, [], []));
            }

            if (null != $organizations) {
                $params['hiddenFilters']['organizations'] = array_map(function (Organization $organization) {
                    return $organization->getUuid();
                }, $organizations);
            }
        }

        return new JsonResponse(
            $this->finder->search(SessionUser::class, $params)
        );
    }

    /**
     * @Route("/{id}/users/{type}", name="apiv2_cursus_session_add_users", methods={"PATCH"})
     * @EXT\ParamConverter("session", class="Claroline\CursusBundle\Entity\Session", options={"mapping": {"id": "uuid"}})
     */
    public function addUsersAction(Session $session, string $type, Request $request): JsonResponse
    {
        $this->checkPermission('REGISTER', $session, [], true);

        $users = $this->decodeIdsString($request, User::class);
        $nbUsers = count($users);

        if (AbstractRegistration::LEARNER === $type && !$this->manager->checkSessionCapacity($session, $nbUsers)) {
            return new JsonResponse(['errors' => [
                $this->translator->trans('users_limit_reached', ['%count%' => $nbUsers], 'cursus'),
            ]], 422); // not the best status (same as form validation errors)
        }

        $sessionUsers = $this->manager->addUsers($session, $users, $type, true);

        return new JsonResponse(array_map(function (SessionUser $sessionUser) {
            return $this->serializer->serialize($sessionUser);
        }, $sessionUsers));
    }

    /**
     * @Route("/{id}/users/{type}", name="apiv2_cursus_session_remove_users", methods={"DELETE"})
     * @EXT\ParamConverter("session", class="Claroline\CursusBundle\Entity\Session", options={"mapping": {"id": "uuid"}})
     */
    public function removeUsersAction(Session $session, Request $request): JsonResponse
    {
        $this->checkPermission('REGISTER', $session, [], true);

        $sessionUsers = $this->decodeIdsString($request, SessionUser::class);

        foreach ($sessionUsers as $sessionUser) {
            $cancellation = new SessionCancellation();
            $cancellation->setUser($sessionUser->getUser());
            $cancellation->setSession($sessionUser->getSession());
            $cancellation->setInscriptionUuid($sessionUser->getUuid());
            $this->om->persist($cancellation);
        }

        $this->manager->removeUsers($session, $sessionUsers);

        return new JsonResponse(null, 204);
    }

    /**
     * @Route("/{id}/groups/{type}", name="apiv2_cursus_session_list_groups", methods={"GET"})
     * @EXT\ParamConverter("session", class="Claroline\CursusBundle\Entity\Session", options={"mapping": {"id": "uuid"}})
     */
    public function listGroupsAction(Session $session, string $type, Request $request): JsonResponse
    {
        $this->checkPermission('REGISTER', $session, [], true);

        $params = $request->query->all();
        if (!isset($params['hiddenFilters'])) {
            $params['hiddenFilters'] = [];
        }
        $params['hiddenFilters']['session'] = $session->getUuid();
        $params['hiddenFilters']['type'] = $type;

        return new JsonResponse(
            $this->finder->search(SessionGroup::class, $params)
        );
    }

    /**
     * @Route("/{id}/groups/{type}", name="apiv2_cursus_session_add_groups", methods={"PATCH"})
     * @EXT\ParamConverter("session", class="Claroline\CursusBundle\Entity\Session", options={"mapping": {"id": "uuid"}})
     */
    public function addGroupsAction(Session $session, string $type, Request $request): JsonResponse
    {
        $this->checkPermission('REGISTER', $session, [], true);

        $groups = $this->decodeIdsString($request, Group::class);
        $nbUsers = 0;

        foreach ($groups as $group) {
            $nbUsers += count($group->getUsers()->toArray());
        }

        if (AbstractRegistration::LEARNER === $type && !$this->manager->checkSessionCapacity($session, $nbUsers)) {
            return new JsonResponse(['errors' => [
                $this->translator->trans('users_limit_reached', ['%count%' => $nbUsers], 'cursus'),
            ]], 422); // not the best status (same as form validation errors)
        }

        $sessionGroups = $this->manager->addGroups($session, $groups, $type);

        return new JsonResponse(array_map(function (SessionGroup $sessionGroup) {
            return $this->serializer->serialize($sessionGroup);
        }, $sessionGroups));
    }

    /**
     * @Route("/{id}/pending", name="apiv2_cursus_session_add_pending", methods={"PATCH"})
     * @EXT\ParamConverter("session", class="Claroline\CursusBundle\Entity\Session", options={"mapping": {"id": "uuid"}})
     */
    public function addPendingAction(Session $session, Request $request): JsonResponse
    {
        $this->checkPermission('REGISTER', $session, [], true);

        $users = $this->decodeIdsString($request, User::class);
        $sessionUsers = $this->manager->addUsers($session, $users, AbstractRegistration::LEARNER, false);

        return new JsonResponse(array_map(function (SessionUser $sessionUser) {
            return $this->serializer->serialize($sessionUser);
        }, $sessionUsers));
    }

    /**
     * @Route("/{id}/self/register", name="apiv2_cursus_session_self_register", methods={"PUT"})
     * @EXT\ParamConverter("session", class="Claroline\CursusBundle\Entity\Session", options={"mapping": {"id": "uuid"}})
     * @EXT\ParamConverter("user", converter="current_user", options={"allowAnonymous"=false})
     */
    public function selfRegisterAction(Session $session, User $user, Request $request): JsonResponse
    {
        $this->checkPermission('OPEN', $session, [], true);

        if (!$session->getPublicRegistration() && !$session->getAutoRegistration()) {
            throw new AccessDeniedException();
        }

        $registrationData = $this->decodeRequest($request);

        $sessionUsers = $this->manager->addUsers($session, [$user], AbstractRegistration::LEARNER, false, [
            $user->getUuid() => $registrationData,
        ]);

        return new JsonResponse($this->serializer->serialize($sessionUsers[0]));
    }

    /**
     * This is the endpoint used by confirmation email.
     *
     * @Route("/{id}/self/confirm", name="apiv2_cursus_session_self_confirm", methods={"GET"})
     * @EXT\ParamConverter("session", class="Claroline\CursusBundle\Entity\Session", options={"mapping": {"id": "uuid"}})
     * @EXT\ParamConverter("user", converter="current_user", options={"allowAnonymous"=false})
     */
    public function selfConfirmAction(Session $session, User $user): RedirectResponse
    {
        $this->checkPermission('OPEN', $session, [], true);

        $sessionUser = $this->om->getRepository(SessionUser::class)->findOneBy(['session' => $session, 'user' => $user]);
        if ($sessionUser && !$sessionUser->isConfirmed()) {
            $this->manager->confirmUsers([$sessionUser]);
        }

        return new RedirectResponse(
            $this->routingHelper->desktopUrl('trainings').'/catalog/'.$session->getCourse()->getSlug().'/'.$session->getUuid()
        );
    }

    /**
     * @Route("/{id}/invite/all", name="apiv2_cursus_session_invite_all", methods={"PUT"})
     * @EXT\ParamConverter("session", class="Claroline\CursusBundle\Entity\Session", options={"mapping": {"id": "uuid"}})
     */
    public function inviteAllAction(Session $session): JsonResponse
    {
        $this->checkPermission('REGISTER', $session, [], true);

        $this->manager->inviteAllSessionLearners($session);

        return new JsonResponse(null, 204);
    }

    /**
     * @Route("/{id}/stats", name="apiv2_cursus_session_stats", methods={"GET"})
     * @EXT\ParamConverter("session", class="Claroline\CursusBundle\Entity\Session", options={"mapping": {"id": "uuid"}})
     */
    public function getStatsAction(Session $session): JsonResponse
    {
        $this->checkPermission('REGISTER', $session, [], true);

        $stats = $this->om->getRepository(Course::class)->getRegistrationStats($session->getCourse(), $session);

        return new JsonResponse([
            'total' => $stats['total'],
            'fields' => array_map(function (array $fieldStats) {
                return [
                    'field' => $this->serializer->serialize($fieldStats['field']),
                    'values' => $fieldStats['values'],
                ];
            }, $stats['fields']),
        ]);
    }

    /**
     * @Route("/{id}/cancellations", name="apiv2_cursus_session_list_cancellations", methods={"GET"})
     * @EXT\ParamConverter("session", class="Claroline\CursusBundle\Entity\Session", options={"mapping": {"id": "uuid"}})
     */
    public function listCancellationAction(Session $session, Request $request): JsonResponse
    {
        $this->checkPermission('REGISTER', $session, [], true);

        $params = $request->query->all();
        if (!isset($params['hiddenFilters'])) {
            $params['hiddenFilters'] = [];
        }
        $params['hiddenFilters']['session'] = $session->getUuid();

        return new JsonResponse(
            $this->finder->search(SessionCancellation::class, $params)
        );
    }

    private function checkToolAccess(?string $rights = 'OPEN'): bool
    {
        $trainingsTool = $this->toolManager->getOrderedTool('trainings', Tool::DESKTOP);

        if (is_null($trainingsTool) || !$this->authorization->isGranted($rights, $trainingsTool)) {
            return false;
        }

        return true;
    }

    private function isTutor(array $registrations)
    {
        foreach ($registrations as $registration) {
            if ('tutor' == $registration->getType()) {
                return true;
            }
        }

        return false;
    }
}
