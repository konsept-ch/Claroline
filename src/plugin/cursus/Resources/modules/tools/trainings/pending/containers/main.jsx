import {connect} from 'react-redux'
import {withReducer} from '#/main/app/store/reducer'
import {selectors} from '#/plugin/cursus/tools/trainings/pending/store'
import {selectors as toolSelectors, reducer} from '#/main/core/tool/store'
import {PendingMain as PendingComponent} from '#/plugin/cursus/tools/trainings/pending/components/main'

const PendingMain = withReducer(selectors.STORE_NAME, reducer)(
  connect(
    (state) => ({
      path: toolSelectors.path(state)
    })
  )(PendingComponent)
)
		
export {
  PendingMain
}
		