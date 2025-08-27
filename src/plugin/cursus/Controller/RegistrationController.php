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
use Claroline\CoreBundle\Security\PermissionCheckerTrait;
use Claroline\CursusBundle\Entity\Registration\SessionUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @Route("/cursus_registration")
 */
class RegistrationController extends AbstractCrudController
{
    use PermissionCheckerTrait;

    private const TAGS = [
        'emcc' => ['MANAGE_EMCC', 'EMCC'],
        'pci' => ['MANAGE_PCI', 'PCi']
    ];

    public function __construct(
        AuthorizationCheckerInterface $authorization,
    ) {
        $this->authorization = $authorization;
    }

    public function getName()
    {
        return 'cursus_registrations';
    }

    public function getClass()
    {
        return SessionUser::class;
    }

    public function getIgnore()
    {
        return ['copyBulk', 'doc', 'exist', 'list'];
    }

    /**
     * @Route("/{tag}", name="apiv2_cursus_registration", methods={"GET"})
     */
    public function listByTagAction(string $tag, Request $request): JsonResponse
    {
        if (!isset(self::TAGS[$tag])) throw new AccessDeniedException('Permission denied');
        
        $meta = self::TAGS[$tag];
        $this->checkPermission($meta[0], null, [], true);

        return new JsonResponse(
            $this->finder->search(SessionUser::class, array_merge(
                $request->query->all(),
                ['hiddenFilters' => ['tag' => $meta[1]]]
            ))
        );
    }
}
