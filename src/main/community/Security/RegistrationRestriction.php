<?php

namespace Claroline\CommunityBundle\Security;

use Claroline\CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * CEP : un compte dont l'organisation principale est une organisation d'attente
 * (« Entreprise non référencée ») doit compléter son profil avant de s'inscrire.
 *
 * Utilisé par ProfileController::requirementsAction (message « Informations manquantes »
 * de la fenêtre d'inscription) et par les routes d'auto-inscription du plugin cursus.
 *
 * L'utilisateur se débloque seul : choisir son organisation dans le champ de profil
 * « ORGANISATION / EMPLOYEUR » met à jour son organisation principale.
 */
final class RegistrationRestriction
{
    /**
     * Codes (claro__organization.code) des organisations qui interdisent l'auto-inscription.
     */
    public const BLOCKED_ORGANIZATION_CODES = ['NREF'];

    public static function isRestricted(User $user): bool
    {
        $organization = $user->getMainOrganization();

        return $organization && in_array($organization->getCode(), self::BLOCKED_ORGANIZATION_CODES, true);
    }

    /**
     * 422 plutôt que 403 : l'interface associe ses messages au code HTTP, et ces routes
     * renvoient déjà des 403 pour d'autres raisons (inscriptions fermées, session commencée).
     */
    public static function createResponse(): JsonResponse
    {
        return new JsonResponse([
            'error' => 'profile_incomplete',
            'message' => 'Veuillez compléter votre profil avant de procéder à l\'inscription.',
        ], 422);
    }
}
