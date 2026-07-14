<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CursusBundle\Repository;

use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Claroline\CursusBundle\Entity\Registration\AbstractRegistration;
use Claroline\CursusBundle\Entity\Session;
use Doctrine\ORM\EntityRepository;

class SessionRepository extends EntityRepository
{
    public function getUniqueCode(string $code): string
    {
        $existingCodes = $this->findSessionCodesWithPrefix($code);
        if (empty($existingCodes)) {
            return $code;
        }

        $existingCodes = array_flip(array_map('strtoupper', $existingCodes));
        $existingCodes[strtoupper($code)] = true;

        $index = 1;
        while (isset($existingCodes[strtoupper($code).'_'.$index])) {
            ++$index;
        }

        return $code.'_'.$index;
    }

    /**
     * Returns the list of session codes starting with $prefix.
     */
    public function findSessionCodesWithPrefix(string $prefix): array
    {
        return array_map(
            function (array $session) {
                return $session['code'];
            },
            $this->_em->createQuery('
                SELECT UPPER(s.code) AS code
                FROM Claroline\CursusBundle\Entity\Session s
                WHERE UPPER(s.code) LIKE :search
            ')
            ->setParameter('search', strtoupper($prefix).'%')
            ->getResult()
        );
    }

    public function findByWorkspace(Workspace $workspace)
    {
        return $this->_em
            ->createQuery('
                SELECT s FROM Claroline\CursusBundle\Entity\Session AS s
                WHERE s.workspace = :workspace
            ')
            ->setParameters([
                'workspace' => $workspace,
            ])
            ->getResult();
    }

    public function countParticipants(Session $session)
    {
        return [
            'tutors' => $this->countTutors($session),
            'learners' => $this->countLearners($session),
            'pending' => $this->countPending($session),
        ];
    }

    public function countTutors(Session $session)
    {
        return $this->countUsers($session, AbstractRegistration::TUTOR);
    }

    public function countLearners(Session $session)
    {
        $count = $this->countUsers($session, AbstractRegistration::LEARNER);

        // add groups count
        $sessionGroups = $this->_em
            ->createQuery('
                SELECT sg FROM Claroline\CursusBundle\Entity\Registration\SessionGroup AS sg
                WHERE sg.type = :registrationType
                  AND sg.session = :session
            ')
            ->setParameters([
                'registrationType' => AbstractRegistration::LEARNER,
                'session' => $session,
            ])
            ->getResult();

        foreach ($sessionGroups as $sessionGroup) {
            $count += $sessionGroup->getGroup()->getUsers()->count();
        }

        return $count;
    }

    public function countPending(Session $session)
    {
        return (int) $this->_em
            ->createQuery('
                SELECT COUNT(su) FROM Claroline\CursusBundle\Entity\Registration\SessionUser AS su
                INNER JOIN su.user u
                WHERE su.type = :registrationType
                  AND su.session = :session
                  AND su.state < 2
                  AND (su.confirmed = 0 OR su.state = 0)
                  AND u.isRemoved = 0
            ')
            ->setParameters([
                'registrationType' => AbstractRegistration::LEARNER,
                'session' => $session,
            ])
            ->getSingleScalarResult();
    }

    private function countUsers(Session $session, string $type)
    {
        return (int) $this->_em
            ->createQuery('
                SELECT COUNT(su) FROM Claroline\CursusBundle\Entity\Registration\SessionUser AS su
                INNER JOIN su.user u
                WHERE su.type = :registrationType
                  AND su.session = :session
                  AND (su.state = 1 OR su.state = 4 OR su.state = 5 OR su.state = 6)
                  AND u.isRemoved = 0
            ')
            ->setParameters([
                'registrationType' => $type,
                'session' => $session,
            ])
            ->getSingleScalarResult();
    }
}
