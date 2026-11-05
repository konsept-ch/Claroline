<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\AuthenticationBundle\Controller;

use Claroline\AppBundle\Controller\AbstractCrudController;
use Claroline\AuthenticationBundle\Entity\IpUser;
use Claroline\CoreBundle\Entity\Tool\AdminTool;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/ip_user")
 */
class IpUserController extends AbstractCrudController
{
    public function getClass()
    {
        return IpUser::class;
    }

    public function getName()
    {
        return 'ip_user';
    }

    /**
     * Une IP enregistrée connecte automatiquement au compte associé : la liste est réservée à
     * l'outil d'administration qui la gère (Intégrations > IP).
     */
    protected function checkCrudReadPermission(string $action, $object = null): void
    {
        $tool = $this->om->getRepository(AdminTool::class)->findOneBy(['name' => 'integration']);
        if (!$tool || !$this->isCrudGranted('OPEN', $tool)) {
            throw new AccessDeniedException();
        }
    }
}
