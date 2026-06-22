import {connect} from 'react-redux'

import {withReducer} from '#/main/app/store/components/withReducer'
import {actions as listActions} from '#/main/app/content/list/store'
import {selectors as securitySelectors} from '#/main/app/security/store'

import {reducer, selectors} from '#/plugin/cursus/tools/trainings/session/store'
import {AccountTrainingsMain as AccountTrainingsMainComponent} from '#/plugin/cursus/account/trainings/components/main'

const AccountTrainingsMain = withReducer(selectors.STORE_NAME, reducer)(
  connect(
    (state) => ({
      currentUser: securitySelectors.currentUser(state)
    }),
    (dispatch) => ({
      invalidateList() {
        dispatch(listActions.invalidateData(selectors.STORE_NAME))
      }
    })
  )(AccountTrainingsMainComponent)
)

export {
  AccountTrainingsMain
}
