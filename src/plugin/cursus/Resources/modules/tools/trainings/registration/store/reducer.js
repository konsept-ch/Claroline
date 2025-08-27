import {combineReducers} from '#/main/app/store/reducer'
import {makeListReducer} from '#/main/app/content/list/store'

import {selectors} from '#/plugin/cursus/tools/trainings/registration/store/selectors'

export const reducer = combineReducers({
	list: makeListReducer(selectors.LIST_NAME)
})
