import {createSelector} from 'reselect'

const STORE_NAME = 'notificationsMenu'

// Guard for missing/injected reducer so selectors don't crash before mount
const store = (state) => state[STORE_NAME] || {count: 0, loaded: false, results: []}

const count = createSelector(
  [store],
  (store) => store.count
)

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
  count,
  loaded,
  results
}
