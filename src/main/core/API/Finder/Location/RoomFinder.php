<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\API\Finder\Location;

use Claroline\AppBundle\API\Finder\AbstractFinder;
use Claroline\CoreBundle\Entity\Location\Room;
use Doctrine\ORM\QueryBuilder;

class RoomFinder extends AbstractFinder
{
    /*
    SELECT DISTINCT r.event_name FROM claro_location_room AS r
    WHERE r.location_id = 2
    AND r.id NOT IN (
	    SELECT r2.id FROM claro_location_room AS r2
	    INNER JOIN claro_planned_object AS o2 ON o2.room_id = r2.id
	    WHERE '2022-08-22 17:00:00' > o2.start_date
	    AND '2022-08-22 08:30:00' < o2.end_date
    )
    */

    public static function getClass(): string
    {
        return Room::class;
    }

    public function configureQueryBuilder(QueryBuilder $qb, array $searches = [], array $sortBy = null, array $options = ['count' => false, 'page' => 0, 'limit' => -1])
    {
        $qb->join('obj.location', 'l');

        foreach ($searches as $filterName => $filterValue) {
            switch ($filterName) {
                case 'location':
                    $qb->andWhere("l.uuid = :{$filterName}");
                    $qb->setParameter($filterName, $filterValue);
                    break;

                case 'range':
                    if ('undefined' != $filterValue[0] && 'undefined' != $filterValue[1])
                    {
                        $qb->andWhere('obj.id NOT IN (
	                        SELECT r.id FROM Claroline\CoreBundle\Entity\Location\Room r
	                        INNER JOIN Claroline\CoreBundle\Entity\Planning\PlannedObject o
	                        INNER JOIN o.room rr
	                        WHERE rr.id = r.id
                            AND :end > o.startDate AND :start < o.endDate
                        )');
                        $qb->setParameter('start', $filterValue[0]);
                        $qb->setParameter('end', $filterValue[1]);
                    }
                    break;

                default:
                    $this->setDefaults($qb, $filterName, $filterValue);
                    break;
            }
        }

        return $qb;
    }
}
