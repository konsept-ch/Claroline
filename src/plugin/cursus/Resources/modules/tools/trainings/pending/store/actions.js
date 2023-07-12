import {makeActionCreator} from '#/main/app/store/actions'

import {API_REQUEST, url} from '#/main/app/api'

export const LOAD_PENDING_LIST = 'LOAD_PENDING_LIST'

export const actions = {}

actions.loadPendingList = makeActionCreator(LOAD_PENDING_LIST)

actions.confirmPending = (sessionId, users) => ({
  [API_REQUEST]: {
    url: url(['apiv2_cursus_session_confirm_pending', {id: sessionId}], {ids: users.map(user => user.id)}),
    request: {
      method: 'PUT'
    },
    success: (data, dispatch) => dispatch(actions.loadPendingList())
  }
})

actions.validatePending = (sessionId, users) => ({
  [API_REQUEST]: {
    url: url(['apiv2_cursus_session_validate_pending', {id: sessionId}], {ids: users.map(user => user.id)}),
    request: {
      method: 'PUT'
    },
    success: (data, dispatch) => dispatch(actions.loadPendingList())
  }
})