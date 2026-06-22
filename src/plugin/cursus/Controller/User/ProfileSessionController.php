<?php

namespace Claroline\CursusBundle\Controller\User;

use Claroline\AppBundle\API\FinderProvider;
use Claroline\CoreBundle\Entity\User;
use Claroline\CursusBundle\Entity\Session;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Exposes API for the sessions of a given profile user.
 *
 * @Route("/users")
 */
class ProfileSessionController
{
    /** @var AuthorizationCheckerInterface */
    private $authorization;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var FinderProvider */
    private $finder;

    public function __construct(
        AuthorizationCheckerInterface $authorization,
        TokenStorageInterface $tokenStorage,
        FinderProvider $finder
    ) {
        $this->authorization = $authorization;
        $this->tokenStorage = $tokenStorage;
        $this->finder = $finder;
    }

    /**
     * List the active (in progress and forthcoming) sessions of the given user.
     *
     * @Route("/{username}/sessions/active", name="apiv2_cursus_user_sessions_active", methods={"GET"})
     * @EXT\ParamConverter("user", options={"mapping": {"username": "username"}})
     */
    public function listActiveAction(User $user, Request $request): JsonResponse
    {
        if (!$this->authorization->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new AccessDeniedException();
        }

        $params = $request->query->all();
        $params['hiddenFilters'] = [];
        $params['hiddenFilters']['user'] = $user->getUuid();
        $params['hiddenFilters']['terminated'] = false;

        return new JsonResponse(
            $this->finder->search(Session::class, $params)
        );
    }

    /**
     * List the ended sessions of the given user.
     *
     * @Route("/{username}/sessions/ended", name="apiv2_cursus_user_sessions_ended", methods={"GET"})
     * @EXT\ParamConverter("user", options={"mapping": {"username": "username"}})
     */
    public function listEndedAction(User $user, Request $request): JsonResponse
    {
        if (!$this->authorization->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new AccessDeniedException();
        }

        $params = $request->query->all();
        $params['hiddenFilters'] = [];
        $params['hiddenFilters']['user'] = $user->getUuid();
        $params['hiddenFilters']['terminated'] = true;

        return new JsonResponse(
            $this->finder->search(Session::class, $params)
        );
    }

    /**
     * List the sessions for which the given user is in pending list.
     *
     * @Route("/{username}/sessions/pending", name="apiv2_cursus_user_sessions_pending", methods={"GET"})
     * @EXT\ParamConverter("user", options={"mapping": {"username": "username"}})
     */
    public function listPendingAction(User $user, Request $request): JsonResponse
    {
        if (!$this->authorization->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new AccessDeniedException();
        }

        $params = $request->query->all();
        $params['hiddenFilters'] = [];
        $params['hiddenFilters']['userPending'] = $user->getUuid();

        return new JsonResponse(
            $this->finder->search(Session::class, $params)
        );
    }
}
