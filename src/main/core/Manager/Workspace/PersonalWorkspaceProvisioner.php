<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Manager\Workspace;

use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\Resource\ResourceNode;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Entity\Workspace\Workspace;

/**
 * Garantit qu'un compte a son espace personnel.
 *
 * CEP — l'inscription publique passe NO_PERSONAL_WORKSPACE depuis le 09.06.2026 (23a1847a7b),
 * pour alleger une requete qui depassait la memoire en production. Les imports CSV le
 * passent aussi par defaut. Ces comptes n'ont donc pas d'espace, et leurs attestations
 * n'ont nulle part ou aller. Plutot que de recreer l'espace pendant l'inscription, on le
 * garantit ici, dans une requete a part : par la commande claroline:user:personal-workspaces
 * pour les comptes existants, puis a l'activation du compte (lot du 10.12.2026, avec les
 * autres changements de la creation de compte).
 *
 * L'espace personnel prend le username comme code, unique. Avant le 09.06.2026, des
 * inscriptions interrompues ont laisse des espaces personnels a moitie copies, rattaches a
 * personne, qui bloquent ce code. Un tel espace est renomme, pas supprime, puis un espace
 * neuf est cree. Un code porte par l'espace d'un autre compte n'est jamais touche.
 *
 * docs/20-former22/attestations-flux.md, etape 5.
 */
class PersonalWorkspaceProvisioner
{
    /** Le compte avait deja son espace : rien n'est fait. */
    const EXISTING = 'existing';
    /** L'espace a ete cree. */
    const CREATED = 'created';
    /** Un espace orphelin bloquait le code : il a ete renomme, puis l'espace a ete cree. */
    const CREATED_AFTER_ORPHAN = 'created_after_orphan';
    /** Le code est pris par un espace qu'on ne doit pas toucher : reparation manuelle. */
    const CODE_TAKEN = 'code_taken';

    /** Suffixe donne au code d'un espace orphelin mis de cote. */
    const ORPHAN_SUFFIX = '~orphan-';

    /** @var ObjectManager */
    private $om;
    /** @var WorkspaceManager */
    private $workspaceManager;

    public function __construct(ObjectManager $om, WorkspaceManager $workspaceManager)
    {
        $this->om = $om;
        $this->workspaceManager = $workspaceManager;
    }

    /**
     * Ne cree rien et ne modifie rien : dit seulement ce que ensure() ferait.
     */
    public function diagnose(User $user): string
    {
        if ($user->getPersonalWorkspace()) {
            return static::EXISTING;
        }

        $conflict = $this->findWorkspaceByCode($user->getUsername());
        if (empty($conflict)) {
            return static::CREATED;
        }

        return $this->isAbandoned($conflict) ? static::CREATED_AFTER_ORPHAN : static::CODE_TAKEN;
    }

    /**
     * Cree l'espace personnel s'il manque, et rend ce qui a ete fait (une des constantes).
     */
    public function ensure(User $user): string
    {
        $diagnosis = $this->diagnose($user);

        switch ($diagnosis) {
            case static::CREATED_AFTER_ORPHAN:
                $orphan = $this->findWorkspaceByCode($user->getUsername());
                $orphan->setCode($user->getUsername().static::ORPHAN_SUFFIX.$orphan->getId());
                $this->om->persist($orphan);
                $this->om->flush();
                // no break
            case static::CREATED:
                $this->workspaceManager->createPersonalWorkspace($user);
                break;
        }

        return $diagnosis;
    }

    /**
     * Un espace qu'on peut mettre de cote sans risque : personnel, rattache a aucun compte,
     * et sans autre contenu que sa racine. C'est ce que laisse une copie interrompue.
     */
    public function isAbandoned(Workspace $workspace): bool
    {
        if (!$workspace->isPersonal()) {
            return false;
        }

        $owner = $this->om->getRepository(User::class)->findOneBy(['personalWorkspace' => $workspace]);
        if (!empty($owner)) {
            return false;
        }

        return $this->countResourceNodes($workspace) <= 1;
    }

    private function findWorkspaceByCode(string $code): ?Workspace
    {
        return $this->om->getRepository(Workspace::class)->findOneBy(['code' => $code]);
    }

    private function countResourceNodes(Workspace $workspace): int
    {
        return (int) $this->om->getRepository(ResourceNode::class)->count(['workspace' => $workspace]);
    }
}
