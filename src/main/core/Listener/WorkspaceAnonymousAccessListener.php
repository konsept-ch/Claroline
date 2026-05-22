<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Listener;

use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\AuthenticationBundle\Security\Authentication\Authenticator;
use Claroline\CoreBundle\Entity\DataSource;
use Claroline\CoreBundle\Entity\Resource\ResourceNode;
use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Claroline\CoreBundle\Manager\Workspace\WorkspaceRestrictionsManager;
use Claroline\CoreBundle\Security\PlatformRoles;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Re-applies a workspace-scoped anonymous token after a valid code unlock.
 *
 * The token only carries the role of the targeted workspace and a dedicated
 * marker role, so public ROLE_ANONYMOUS rights remain available only to the
 * standard anonymous flow.
 */
class WorkspaceAnonymousAccessListener
{
    /** @var AuthorizationCheckerInterface */
    private $authorization;
    /** @var TokenStorageInterface */
    private $tokenStorage;
    /** @var ObjectManager */
    private $om;
    /** @var Authenticator */
    private $authenticator;
    /** @var WorkspaceRestrictionsManager */
    private $restrictionsManager;

    public function __construct(
        AuthorizationCheckerInterface $authorization,
        TokenStorageInterface $tokenStorage,
        ObjectManager $om,
        Authenticator $authenticator,
        WorkspaceRestrictionsManager $restrictionsManager
    ) {
        $this->authorization = $authorization;
        $this->tokenStorage = $tokenStorage;
        $this->om = $om;
        $this->authenticator = $authenticator;
        $this->restrictionsManager = $restrictionsManager;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return;
        }

        if ($this->authorization->isGranted('IS_AUTHENTICATED_FULLY')) {
            return;
        }

        if (in_array('ROLE_USURPATE_WORKSPACE_ROLE', $token->getRoleNames(), true)) {
            return;
        }

        $workspace = $this->resolveWorkspace($event);
        if (!$workspace || !$this->restrictionsManager->isUnlocked($workspace)) {
            return;
        }

        $defaultRole = $workspace->getDefaultRole();
        if (!$defaultRole) {
            return;
        }

        $this->authenticator->createAnonymousToken([
            PlatformRoles::WORKSPACE_ACCESS,
            $defaultRole->getName(),
        ]);
    }

    private function resolveWorkspace(RequestEvent $event): ?Workspace
    {
        $request = $event->getRequest();
        $route = (string) $request->attributes->get('_route');

        if (in_array($route, ['claro_workspace_open', 'claro_workspace_unlock'], true)) {
            $slug = $request->attributes->get('slug');

            if (!$slug) {
                return null;
            }

            /** @var Workspace|null $workspace */
            $workspace = $this->om->getRepository(Workspace::class)->findOneBy(['slug' => $slug]);

            return $workspace;
        }

        if ('claro_workspace_open_tool' === $route) {
            $workspaceId = $request->attributes->get('id');

            if (!$workspaceId) {
                return null;
            }

            /** @var Workspace|null $workspace */
            $workspace = $this->om->getRepository(Workspace::class)->findOneBy(['uuid' => $workspaceId]);

            return $workspace;
        }

        if (in_array($route, ['claro_resource_load', 'claro_resource_load_embedded', 'claro_resource_unlock', 'claro_resource_action'], true)) {
            $resourceId = $request->attributes->get('id');

            if (!$resourceId) {
                return null;
            }

            /** @var ResourceNode|null $resourceNode */
            $resourceNode = $this->om->getRepository(ResourceNode::class)->findOneByUuidOrSlug($resourceId);
            if (!$resourceNode) {
                return null;
            }

            return $resourceNode->getWorkspace();
        }

        if ('apiv2_data_source' === $route && DataSource::CONTEXT_WORKSPACE === $request->attributes->get('context')) {
            $workspaceId = $request->attributes->get('contextId');

            if (!$workspaceId) {
                return null;
            }

            /** @var Workspace|null $workspace */
            $workspace = $this->om->getRepository(Workspace::class)->findOneBy(['uuid' => $workspaceId]);

            return $workspace;
        }

        return null;
    }
}
