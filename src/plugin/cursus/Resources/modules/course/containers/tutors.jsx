import {connect} from 'react-redux'

import {actions} from '#/plugin/cursus/tools/trainings/catalog/store'
import {CourseTutors as CourseTutorsComponent} from '#/plugin/cursus/course/components/tutors'

const CourseTutors = connect(
  null,
  (dispatch) => ({
    addUsers(sessionId, users, type) {
      dispatch(actions.addUsers(sessionId, users, type))
    }
  })
)(CourseTutorsComponent)

export {
  CourseTutors
}
