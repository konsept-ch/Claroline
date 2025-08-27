import {connect} from 'react-redux'

import {actions} from '#/plugin/cursus/tools/trainings/catalog/store'
import {CoursePresences as CoursePresencesComponent} from '#/plugin/cursus/course/components/presences'

const CoursePresences = connect(
  null,
  (dispatch) => ({
    addUsers(sessionId, users, type) {
      dispatch(actions.addUsers(sessionId, users, type))
    },
    validateParticipation(sessionId, users) {
      dispatch(actions.validateParticipation(sessionId, users))
    }
  })
)(CoursePresencesComponent)

export {
  CoursePresences
}
