import {connect} from 'react-redux'
import {withReducer} from '#/main/app/store/reducer'
import {selectors, actions, reducer} from '#/plugin/cursus/tools/trainings/subscription/store'
import {SubscriptionList as SubscriptionComponent} from '#/plugin/cursus/tools/trainings/subscription/components/list'

const SubscriptionList = withReducer(selectors.STORE_NAME, reducer)(
  connect(
    null,
    (dispatch) => ({
      setYear(year) {
        dispatch(actions.setYear(year))
      }
    })
  )(SubscriptionComponent)
)
		
export {
  SubscriptionList
}
		