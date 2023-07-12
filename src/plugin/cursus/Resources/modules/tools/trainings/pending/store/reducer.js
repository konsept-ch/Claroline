import {makeReducer, combineReducers} from '#/main/app/store/reducer'
import {makeListReducer} from '#/main/app/content/list/store'

import {selectors} from '#/plugin/cursus/tools/trainings/pending/store/selectors'
import { LOAD_PENDING_LIST } from './actions'

export const reducer = combineReducers({
  pendings: makeListReducer(selectors.LIST_NAME, {}, {
    invalidated: makeReducer(false, {
      [LOAD_PENDING_LIST]: () => true
    })
  })
})
