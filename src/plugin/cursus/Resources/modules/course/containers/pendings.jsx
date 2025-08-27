import {connect} from 'react-redux'

import {actions} from '#/plugin/cursus/tools/trainings/catalog/store'
import {CoursePendings as CoursePendingsComponent} from '#/plugin/cursus/course/components/pendings'

const CoursePendings = connect(
  null,
  (dispatch) => ({
    addUsers(sessionId, users, type) {
      dispatch(actions.addUsers(sessionId, users, type))
    },
    validatePending(sessionId, users) {
      dispatch(actions.validatePending(sessionId, users))
    },
    refusePending(sessionId, users) {
      dispatch(actions.refusePending(sessionId, users))
    }
  })
)(CoursePendingsComponent)

export {
  CoursePendings
}
