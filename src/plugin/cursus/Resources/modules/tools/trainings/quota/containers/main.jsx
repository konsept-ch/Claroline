import {connect} from 'react-redux'
import {withReducer} from '#/main/app/store/reducer'
import {selectors} from '#/plugin/cursus/tools/trainings/quota/store/selectors'
import {selectors as toolSelectors, reducer} from '#/main/core/tool/store'
import {QuotaMain as QuotaRoutes} from '#/plugin/cursus/tools/trainings/quota/components/main'

const QuotaMain = withReducer(selectors.STORE_NAME, reducer)(
	connect(
    	(state) => ({
      		path: toolSelectors.path(state),
		})
	)(QuotaRoutes)
)

export {
  QuotaMain
}
