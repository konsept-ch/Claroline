import {connect} from 'react-redux'

import {withReducer} from '#/main/app/store/components/withReducer'
import {actions as listActions} from '#/main/app/content/list/store'

import {reducer, selectors} from '#/plugin/cursus/tools/trainings/session/store'
import {ProfileTrainingsMain as ProfileTrainingsMainComponent} from '#/plugin/cursus/profile/trainings/components/main'

const ProfileTrainingsMain = withReducer(selectors.STORE_NAME, reducer)(
  connect(
    null,
    (dispatch) => ({
      invalidateList() {
        dispatch(listActions.invalidateData(selectors.STORE_NAME))
      }
    })
  )(ProfileTrainingsMainComponent)
)

export {
  ProfileTrainingsMain
}
