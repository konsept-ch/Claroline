<?php

namespace Claroline\CommunityBundle\Transfer\Importer\User;

use Claroline\AppBundle\API\Crud;
use Claroline\AppBundle\API\Options;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\Group;
use Claroline\CoreBundle\Entity\Organization\Organization;
use Claroline\CoreBundle\Entity\Role;
use Claroline\CoreBundle\Entity\User;
use Claroline\TransferBundle\Transfer\Importer\AbstractImporter;

class Create extends AbstractImporter
{
    /** @var Crud */
    private $crud;
    /** @var ObjectManager */
    private $om;

    public function __construct(Crud $crud, ObjectManager $om)
    {
        $this->crud = $crud;
        $this->om = $om;
    }

    public static function getAction(): array
    {
        return ['user', self::MODE_CREATE];
    }

    public function execute(array $data): array
    {
        // CEP : l'espace personnel est créé par défaut. En amont, il ne l'était que si le fichier
        // portait `meta.personalWorkspace` à vrai : les comptes importés n'avaient donc pas d'espace,
        // et leurs attestations n'avaient nulle part où aller (constaté le 19.11.2026).
        // Une valeur explicitement fausse reste respectée (import JSON). En CSV, l'adaptateur ignore
        // les cellules vides ou à 0 : l'espace y est donc toujours créé.
        $hasWs = true;
        $options = [Options::FORCE_FLUSH];

        if (isset($data['meta']['personalWorkspace'])) {
            $hasWs = (bool) $data['meta']['personalWorkspace'];
        }

        if (!$hasWs) {
            $options[] = Options::NO_PERSONAL_WORKSPACE;
        }

        if (isset($data['mainOrganization'])) {
            $organization = $this->om->getObject($data['mainOrganization'], Organization::class, array_keys($data['mainOrganization']));

            if (!$organization) {
                throw new \Exception('Organization '.implode(',', $data['mainOrganization']).' does not exists');
            }
        }

        $groups = [];
        if (isset($data['groups'])) {
            foreach ($data['groups'] as $group) {
                $object = $this->om->getObject($group, Group::class, array_keys($group));
                if (!$object) {
                    throw new \Exception('Group '.implode(',', $group).' does not exists');
                }

                $groups[] = $object;
            }

            // remove groups from input data to avoid the user serializer to process it
            unset($data['groups']);
        }

        $roles = [];
        if (isset($data['roles'])) {
            foreach ($data['roles'] as $role) {
                $object = $this->om->getObject($role, Role::class, array_keys($role));
                if (!$object) {
                    throw new \Exception('Role '.implode(',', $role).' does not exists');
                }

                $roles[] = $object;
            }

            // remove roles from input data to avoid the user serializer to process it
            unset($data['roles']);
        }

        $user = $this->crud->create(User::class, $data, $options);
        if ($user) {
            if (!empty($groups)) {
                $this->crud->patch($user, 'group', 'add', $groups);
            }

            if (!empty($roles)) {
                $this->crud->patch($user, 'role', 'add', $roles);
            }
        }

        return [
            'create' => [[
                'data' => $data,
                'log' => static::getAction()[0].' created.',
            ]],
        ];
    }

    public function getSchema(?array $options = [], ?array $extra = []): array
    {
        return ['$root' => User::class];
    }

    public function getMode()
    {
        return self::MODE_CREATE;
    }
}
