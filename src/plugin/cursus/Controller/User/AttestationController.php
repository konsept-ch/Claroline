<?php

namespace Claroline\CursusBundle\Controller\User;

use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\Resource\ResourceNode;
use Claroline\CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Lien « Mes attestations » : ouvre le dossier où Former22 dépose les attestations de
 * l'utilisateur courant.
 *
 * Remplace l'URL qui devinait le dossier personnel à partir du username
 * (`cep.swiss/redirection`) : fausse quand le slug du dossier ne suit pas l'e-mail, et
 * toujours tournée vers la production.
 *
 * @Route("/my_attestations")
 */
class AttestationController
{
    /**
     * Nom du dossier créé par Former22 (cep-former22/src/helpers/attestationsResolvers.js).
     */
    public const FOLDER_NAME = 'Mes attestations';

    /** @var TokenStorageInterface */
    private $tokenStorage;
    /** @var ObjectManager */
    private $om;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        ObjectManager $om
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->om = $om;
    }

    /**
     * Redirige vers « Mes attestations », à défaut vers le dossier personnel, à défaut vers
     * la liste des ressources. Un visiteur non connecté est envoyé vers la connexion.
     *
     * @Route("", name="apiv2_cursus_my_attestations_open", methods={"GET"})
     */
    public function openAction(Request $request): RedirectResponse
    {
        $app = $request->getBasePath().'/#';

        $token = $this->tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;
        if (!$user instanceof User) {
            return new RedirectResponse($app.'/login');
        }

        $workspace = $user->getPersonalWorkspace();
        if (!$workspace) {
            return new RedirectResponse($app.'/desktop/resources');
        }

        $repository = $this->om->getRepository(ResourceNode::class);
        $root = $repository->findWorkspaceRoot($workspace);
        if (!$root) {
            return new RedirectResponse($app.'/desktop/resources');
        }

        $folder = $repository->findOneBy(
            ['parent' => $root, 'name' => self::FOLDER_NAME, 'active' => true],
            ['id' => 'ASC']
        );

        return new RedirectResponse($app.'/desktop/resources/'.($folder ?: $root)->getSlug());
    }
}
