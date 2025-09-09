<?php

namespace Claroline\AppBundle\Doctrine;

use Claroline\CursusBundle\Entity\Registration\SessionCancellation;
use Claroline\CursusBundle\Entity\Registration\SessionUser;
use Claroline\CursusBundle\Entity\Session;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * Adds a global year-window constraint based on inscription (registration) dates.
 *
 * Recent (archive=0): keep data with registration_date >= cutoffStart (Jan 1 of currentYear-1).
 * Archive (archive=1): keep data with registration_date < cutoffStart.
 *
 * For Session entities, the constraint applies through EXISTS subqueries on
 * claro_cursusbundle_course_session_user and claro_cursusbundle_course_session_cancellation.
 */
class YearWindowFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        $class = $targetEntity->getName();

        // Retrieve parameters (already SQL-quoted by SQLFilter::getParameter)
        // If not set yet, do nothing (filter should be enabled only once parameters are set)
        try {
            $cutoffStart = $this->getParameter("cutoffStart");
            $rawArchive = (string) $this->getParameter("archive");
            $archive = (strpos($rawArchive, "1") !== false) ? 1 : 0;
        } catch (\InvalidArgumentException $e) {
            return "";
        }

        // Direct inscription tables: filter on their own registration date
        if ($class === SessionUser::class || $class === SessionCancellation::class) {
            $op = $archive ? "<" : ">=";
            return sprintf("%s.registration_date %s %s", $targetTableAlias, $op, $cutoffStart);
        }

        // Session table: include/exclude based on existence of inscriptions in the window
        if ($class === Session::class) {
            if ($archive) {
                // Archive: exclude sessions having any inscription in the recent window
                return sprintf(
                    "(NOT EXISTS (SELECT 1 FROM claro_cursusbundle_course_session_user su WHERE su.session_id = %1$s.id AND su.registration_date >= %2$s)
                      AND NOT EXISTS (SELECT 1 FROM claro_cursusbundle_course_session_cancellation sc WHERE sc.session_id = %1$s.id AND sc.registration_date >= %2$s))",
                    $targetTableAlias,
                    $cutoffStart
                );
            }

            // Recent: include sessions having at least one inscription in the recent window
            return sprintf(
                "(EXISTS (SELECT 1 FROM claro_cursusbundle_course_session_user su WHERE su.session_id = %1$s.id AND su.registration_date >= %2$s)
                  OR EXISTS (SELECT 1 FROM claro_cursusbundle_course_session_cancellation sc WHERE sc.session_id = %1$s.id AND sc.registration_date >= %2$s))",
                $targetTableAlias,
                $cutoffStart
            );
        }

        // No constraint for other entities
        return "";
    }
}