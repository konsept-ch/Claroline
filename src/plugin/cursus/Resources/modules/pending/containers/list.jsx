import {connect} from 'react-redux'

import {actions} from '#/plugin/cursus/tools/trainings/pending/store/actions'

import {PendingList as PendingListComponent} from '#/plugin/cursus/pending/components/list'

const PendingList = connect(
  null,
  (dispatch) => ({
    confirmPending(sessionId, users) {
      dispatch(actions.confirmPending(sessionId, users))
    },
    validatePending(sessionId, users) {
      dispatch(actions.validatePending(sessionId, users))
    }
  })
)(PendingListComponent)

export {
  PendingList
}
