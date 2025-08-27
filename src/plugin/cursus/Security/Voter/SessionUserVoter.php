<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CursusBundle\Security\Voter;

use Claroline\CoreBundle\Security\Voter\AbstractVoter;
use Claroline\CursusBundle\Entity\Registration\SessionUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class SessionUserVoter extends AbstractVoter
{
    const MANAGE_EMCC = 'MANAGE_EMCC';
    const MANAGE_PCI = 'MANAGE_PCI';
    const VALIDATE_REGISTRATIONS = 'VALIDATE_REGISTRATIONS';
    const VALIDATE_PRESENCES = 'VALIDATE_PRESENCES';

    public function getClass()
    {
        return SessionUser::class;
    }

    public function checkPermission(TokenInterface $token, $object, array $attributes, array $options)
    {
        switch ($attributes[0]) {
            case self::EDIT:
                return VoterInterface::ACCESS_GRANTED;
            case self::MANAGE_EMCC:
            case self::MANAGE_PCI:
            case self::VALIDATE_REGISTRATIONS:
            case self::VALIDATE_PRESENCES:
                return $this->isToolGranted($attributes[0], 'trainings') ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED;
        }

        return VoterInterface::ACCESS_DENIED;
    }

    public function getSupportedActions()
    {
        return [self::EDIT, self::MANAGE_EMCC, self::MANAGE_PCI, self::VALIDATE_REGISTRATIONS, self::VALIDATE_PRESENCES];
    }
}
