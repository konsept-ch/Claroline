<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Repository\Resource;

use Claroline\CoreBundle\Entity\Resource\ResourceNode;
use Claroline\CoreBundle\Security\PlatformRoles;
use Doctrine\ORM\EntityRepository;

class ResourceRightsRepository extends EntityRepository
{
    /**
     * Returns the maximum rights on a given resource for a set of roles.
     * Used by the ResourceVoter.
     *
     * @param string[] $roles
     *
     * @return int
     */
    public function findMaximumRights(array $roles, ResourceNode $resource)
    {
        // Anonymous visitors normally inherit public rights.
        // A scoped workspace-code token carries ROLE_WORKSPACE_ACCESS instead,
        // and must not be widened to all ROLE_ANONYMOUS rights.
        if (!in_array(PlatformRoles::WORKSPACE_ACCESS, $roles, true) && !in_array('ROLE_ANONYMOUS', $roles, true)) {
            $roles[] = 'ROLE_ANONYMOUS';
        }

        $dql = '
            SELECT rrw.mask
            FROM Claroline\CoreBundle\Entity\Resource\ResourceRights rrw
            JOIN rrw.role role
            JOIN rrw.resourceNode resource
            WHERE ';

        $index = 0;

        foreach ($roles as $key => $role) {
            $dql .= 0 !== $index ? ' OR ' : '';
            $dql .= "resource.id = {$resource->getId()} AND role.name = :role{$key}";
            ++$index;
        }

        $query = $this->_em->createQuery($dql);

        foreach ($roles as $key => $role) {
            $query->setParameter("role{$key}", $role);
        }

        $results = $query->getResult();
        $mask = 0;

        foreach ($results as $result) {
            $mask |= $result['mask'];
        }

        return $mask;
    }

    /**
     * Returns the resource types a set of roles is allowed to create in a given
     * directory.
     *
     * @return array
     */
    public function findCreationRights(array $roles, ResourceNode $node)
    {
        if (0 === count($roles)) {
            throw new \RuntimeException('Roles cannot be empty');
        }

        $dql = '
            SELECT DISTINCT rType.name
            FROM Claroline\CoreBundle\Entity\Resource\ResourceType AS rType
            JOIN rType.rights right
            JOIN right.role role
            JOIN right.resourceNode resource
            WHERE ';

        $index = 0;

        foreach ($roles as $key => $role) {
            $dql .= 0 !== $index ? ' OR ' : '';
            $dql .= "resource.id = :nodeId AND role.name = :role_{$key}";
            ++$index;
        }

        $query = $this->_em->createQuery($dql);
        $query->setParameter('nodeId', $node->getId());

        foreach ($roles as $key => $role) {
            $query->setParameter('role_'.$key, $role);
        }

        return $query->getArrayResult();
    }
}
