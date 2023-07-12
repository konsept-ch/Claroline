import {createSelector} from 'reselect'
import {selectors as cursusSelectors} from '#/plugin/cursus/tools/trainings/store/selectors'

const STORE_NAME = cursusSelectors.STORE_NAME + '.pending'
const LIST_NAME = STORE_NAME + '.pendings'

const store = (state) => state[cursusSelectors.STORE_NAME].pending

const pending = createSelector(
  store,
  (state) => state.pending
)

export const selectors = {
  STORE_NAME,
  LIST_NAME,

  pending
}
