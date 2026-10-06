<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Repository\Planning;

use Claroline\CoreBundle\Library\Normalizer\DateNormalizer;
use Doctrine\ORM\EntityRepository;

class PlanningRepository extends EntityRepository
{
    /**
     * @param string|null $ignoredId - uuid d'un objet planifié à ne pas compter : l'objet lui-même,
     *                                 quand on recontrôle ses nouvelles dates
     */
    public function areDatesAvailable(string $objectId, \DateTimeInterface $start, \DateTimeInterface $end, ?string $ignoredId = null): bool
    {
        $parameters = [
            'startDate' => DateNormalizer::normalize($start),
            'endDate' => DateNormalizer::normalize($end),
            'objectId' => $objectId,
        ];

        if ($ignoredId) {
            $parameters['ignoredId'] = $ignoredId;
        }

        $count = (int) $this->_em
            ->createQuery('
                SELECT COUNT(p)
                FROM Claroline\CoreBundle\Entity\Planning\Planning AS pl 
                LEFT JOIN pl.plannedObjects AS p
                WHERE :endDate > p.startDate
                  AND :startDate < p.endDate
                  AND pl.objectId = :objectId
                  '.($ignoredId ? 'AND p.uuid != :ignoredId' : '').'
            ')
            ->setParameters($parameters)
            ->getSingleScalarResult();

        return 0 === $count;
    }
}
