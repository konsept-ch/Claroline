import {createSelector} from 'reselect'

const STORE_NAME = 'historyMenu'

// The reducer is injected dynamically; guard for cases where the slice is not mounted yet
const store = (state) => state[STORE_NAME] || {loaded: false, results: {}}

const loaded = createSelector(
  [store],
  (store) => store.loaded
)

const results = createSelector(
  [store],
  (store) => store.results
)

export const selectors = {
  STORE_NAME,

  store,
  loaded,
  results
}
