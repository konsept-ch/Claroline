<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CursusBundle\Finder\Registration;

use Claroline\AppBundle\API\Finder\AbstractFinder;
use Claroline\CursusBundle\Entity\Registration\SessionUser;
use Doctrine\ORM\QueryBuilder;

class SessionUserFinder extends AbstractFinder
{
    public static function getClass(): string
    {
        return SessionUser::class;
    }

    public function configureQueryBuilder(QueryBuilder $qb, array $searches = [], array $sortBy = null, array $options = ['count' => false, 'page' => 0, 'limit' => -1])
    {
        $qb->join('obj.user', 'u');
        $qb->join('obj.session', 's');
        $qb->join('s.course', 'c');

        if (!array_key_exists('userDisabled', $searches) && !array_key_exists('user', $searches)) {
            $qb->andWhere('u.isEnabled = TRUE');
            $qb->andWhere('u.isRemoved = FALSE');
        }

        foreach ($searches as $filterName => $filterValue) {
            switch ($filterName) {
                case 'year':
                    $qb->andWhere("year(s.startDate) = :{$filterName}");
                    $qb->setParameter($filterName, $filterValue);
                    break;

                case 'status':
                    if (is_array($filterValue)) $qb->andWhere("(obj.status IN (:{$filterName}))");
                    else $qb->andWhere("(obj.status = :{$filterName})");
                    $qb->setParameter($filterName, $filterValue);
                    break;

                case 'ignored_status':
                    if (is_array($filterValue)) $qb->andWhere("(obj.status NOT IN (:{$filterName}))");
                    else $qb->andWhere("obj.status != :{$filterName}");
                    $qb->setParameter($filterName, $filterValue);
                    break;

                case 'type':
                    $qb->andWhere("(obj.type = :{$filterName})");
                    $qb->setParameter($filterName, $filterValue);
                    break;

                case 'organization':
                    $qb->leftJoin('u.userOrganizationReferences', 'oref');
                    $qb->andWhere("oref.organization = :{$filterName}");
                    $qb->setParameter($filterName, $filterValue);
                    break;

                case 'course':
                    $qb->andWhere("c.uuid = :{$filterName}");
                    $qb->setParameter($filterName, $filterValue);
                    break;

                case 'session':
                    $qb->andWhere("s.uuid = :{$filterName}");
                    $qb->setParameter($filterName, $filterValue);
                    break;

                case 'user':
                    $qb->andWhere("u.uuid = :{$filterName}");
                    $qb->setParameter($filterName, $filterValue);
                    break;

                case 'userDisabled':
                    $qb->andWhere('u.isEnabled = :isEnabled');
                    $qb->andWhere('u.isRemoved = FALSE');
                    $qb->setParameter('isEnabled', !$filterValue);
                    break;

                case 'organizations':
                    $qb->leftJoin('u.userOrganizationReferences', 'oref');
                    $qb->andWhere("oref.organization IN (:{$filterName})");
                    $qb->setParameter($filterName, $filterValue);
                    break;

                case 'pending':
                    if ($filterValue) {
                        $qb->andWhere('(obj.confirmed = 0 OR obj.validated = 0)');
                    } else {
                        $qb->andWhere('(obj.confirmed = 1 AND obj.validated = 1)');
                    }
                    break;

                default:
                    $this->setDefaults($qb, $filterName, $filterValue);
            }
        }

        return $qb;
    }
}
