import {connect} from 'react-redux'

import {hasPermission} from '#/main/app/security'

import {selectors as securitySelectors} from '#/main/app/security/store'
import {selectors as toolSelectors} from '#/main/core/tool/store'

import {actions, selectors} from '#/plugin/cursus/tools/trainings/catalog/store'
import {CourseMain as CourseMainComponent} from '#/plugin/cursus/course/components/main'

const CourseMain = connect(
  (state) => ({
    isAuthenticated: securitySelectors.isAuthenticated(state),
    defaultSession: selectors.defaultSession(state),
    activeSession: selectors.activeSession(state),
    activeSessionRegistration: selectors.activeSessionRegistration(state),
    courseRegistration: selectors.courseRegistration(state),
    availableSessions: selectors.availableSessions(state),
    canValidateRegistrations: hasPermission('validate_registrations', toolSelectors.toolData(state)),
    canValidatePresences: hasPermission('validate_presences', toolSelectors.toolData(state))
  }),
  (dispatch) => ({
    openSession(sessionId) {
      dispatch(actions.openSession(sessionId))
    },
    openForm(slug, defaultProps) {
      dispatch(actions.openForm(slug, defaultProps))
    }
  })
)(CourseMainComponent)

export {
  CourseMain
}
