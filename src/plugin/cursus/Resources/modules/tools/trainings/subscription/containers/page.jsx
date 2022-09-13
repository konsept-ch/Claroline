import {connect} from 'react-redux'
import {actions, selectors} from '#/plugin/cursus/tools/trainings/subscription/store'
import {selectors as quotaSelectors} from '#/plugin/cursus/tools/trainings/quota/store'
import {selectors as toolSelectors} from '#/main/core/tool/store'
import {selectors as securitySelectors} from '#/main/app/security/store'
import {SubscriptionPage as SubscriptionComponent} from '#/plugin/cursus/tools/trainings/subscription/components/page'

const SubscriptionPage = connect(
  (state) => ({
    currentContext: toolSelectors.context(state),
    quota: quotaSelectors.quota(state),
    statistics: selectors.statistics(state),
    filters: selectors.filters(state),
    isAdmin: securitySelectors.isAdmin(state)
  }),
  (dispatch) => ({
    getStatistics(id, year) {
      dispatch(actions.getStatistics(id, year))
    },
    setSubscriptionStatus(year, quotaId, subscriptionId, status, remark) {
      dispatch(actions.setSubscriptionStatus(year, quotaId, subscriptionId, status, remark))
    },
    updateYear(id, year) {
      dispatch(actions.updateYear(id, year))
    }
  })
)(SubscriptionComponent)

export {
  SubscriptionPage
}
