import {trans} from '#/main/app/intl/translation'
import {constants as alertConstants} from '#/main/app/overlays/alert/constants'

/**
 * CEP : message affiché quand le serveur refuse une auto-inscription parce que
 * l'organisation principale du compte est une organisation d'attente
 * (voir Claroline\CommunityBundle\Security\RegistrationRestriction, qui répond en 422).
 *
 * Reprend le message de la fenêtre d'inscription (ProfileController::requirementsAction).
 */
function registrationRestrictionMessages() {
  return {
    [alertConstants.ALERT_STATUS_WARNING]: {
      title: trans('registation_requirements', {}, 'cursus'),
      message: trans('apply_registation_requirements', {}, 'cursus')
    }
  }
}

export {
  registrationRestrictionMessages
}
