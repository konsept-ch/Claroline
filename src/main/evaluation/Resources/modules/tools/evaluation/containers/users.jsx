import {connect} from 'react-redux'

import {selectors as listSelectors} from '#/main/app/content/list/store'
import {selectors as toolSelectors} from '#/main/core/tool/store/selectors'

import {EvaluationUsers as EvaluationUsersComponent} from '#/main/evaluation/tools/evaluation/components/users'
import {actions, selectors} from '#/main/evaluation/tools/evaluation/store'

const EvaluationUsers = connect(
  (state) => ({
    path: toolSelectors.path(state),
    contextId: toolSelectors.contextId(state),
    searchQueryString: listSelectors.queryString(listSelectors.list(state, selectors.STORE_NAME + '.workspaceEvaluations'))
  }),
  (dispatch) => ({
    downloadParticipationCertificate(evaluation) {
      dispatch(actions.downloadParticipationCertificate(evaluation))
    },
    downloadSuccessCertificate(evaluation) {
      dispatch(actions.downloadSuccessCertificate(evaluation))
    }
  })
)(EvaluationUsersComponent)

export {
  EvaluationUsers
}
