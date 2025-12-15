import {createSelector} from 'reselect'

const STORE_NAME = 'favouriteMenu'

// Guard to avoid crashing before the reducer is injected
const store = (state) => state[STORE_NAME] || {loaded: false, results: []}

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
