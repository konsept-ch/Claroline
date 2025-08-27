import {connect} from 'react-redux'

import {hasPermission} from '#/main/app/security'

import {selectors as toolSelectors} from '#/main/core/tool/store'
import {selectors as securitySelectors} from '#/main/app/security/store'
import {actions as listActions} from '#/main/app/content/list/store'
import {selectors as registrationSelectors} from '#/plugin/cursus/tools/trainings/registration/store'

import {TrainingsTool as TrainingsToolComponent} from '#/plugin/cursus/tools/trainings/components/tool'

const TrainingsTool = connect(
  (state) => ({
    authenticated: securitySelectors.isAuthenticated(state),
    canManageQuotas: hasPermission('manage_quotas', toolSelectors.toolData(state)),
    canValidateSubscriptions: hasPermission('validate_subscriptions', toolSelectors.toolData(state)),
    canValidateEmcc: hasPermission('validate_emcc', toolSelectors.toolData(state)),
    canValidatePci: hasPermission('validate_pci', toolSelectors.toolData(state))
  }),
  (dispatch) => ({
    invalidateRegistrationList() {
      dispatch(listActions.invalidateData(registrationSelectors.LIST_NAME))
    }
  })
)(TrainingsToolComponent)

export {
  TrainingsTool
}
