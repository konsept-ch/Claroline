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

use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Security\Voter\AbstractVoter;
use Claroline\CursusBundle\Entity\Quota;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class QuotaVoter extends AbstractVoter
{
    const MANAGE_QUOTAS = 'MANAGE_QUOTAS';
    const VALIDATE_SUBSCRIPTIONS = 'VALIDATE_SUBSCRIPTIONS';

    public function getClass()
    {
        return Quota::class;
    }

    public function checkPermission(TokenInterface $token, $object, array $attributes, array $options)
    {
        switch ($attributes[0]) {
            case self::EDIT:
                return VoterInterface::ACCESS_GRANTED;
            case self::MANAGE_QUOTAS:
                return $this->isToolGranted($attributes[0], 'trainings') ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED;
            case self::VALIDATE_SUBSCRIPTIONS:
                if (!$this->isToolGranted($attributes[0], 'trainings')) {
                    return VoterInterface::ACCESS_DENIED;
                }

                // CEP : le droit sur l'outil ne suffisait pas. Un gestionnaire RH lisait et validait les
                // inscriptions de n'importe quelle organisation dont il connaissait le quota (constaté
                // sur VAL le 26.11.2026 : 28 inscriptions d'un autre service). On applique la même règle
                // que la liste des quotas : ses organisations et leurs sous-organisations.
                return $this->isAdmin($token) || $this->isInUserOrganizations($token, $object)
                    ? VoterInterface::ACCESS_GRANTED
                    : VoterInterface::ACCESS_DENIED;
        }

        return VoterInterface::ACCESS_DENIED;
    }

    private function isInUserOrganizations(TokenInterface $token, Quota $quota): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User || !$quota->getOrganization()) {
            return false;
        }

        $allowed = [];
        $this->collectOrganizationIds($user->getOrganizations(), $allowed);

        return in_array($quota->getOrganization()->getUuid(), $allowed, true);
    }

    private function collectOrganizationIds(iterable $organizations, array &$output): void
    {
        foreach ($organizations as $organization) {
            if (in_array($organization->getUuid(), $output, true)) {
                continue;
            }

            $output[] = $organization->getUuid();
            $this->collectOrganizationIds($organization->getChildren(), $output);
        }
    }

    public function getSupportedActions()
    {
        return [self::EDIT, self::MANAGE_QUOTAS, self::VALIDATE_SUBSCRIPTIONS];
    }
}
