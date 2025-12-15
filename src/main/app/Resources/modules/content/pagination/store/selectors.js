import get from 'lodash/get'

import {constants} from '#/main/app/content/pagination/constants'

// retrieves a list instance in the store
const pagination = (state, paginationName) => get(state, paginationName) || {
  page: 0,
  pageSize: constants.DEFAULT_PAGE_SIZE
}

const pageSize    = (paginationState) => paginationState.pageSize
const currentPage = (paginationState) => paginationState.page

const queryString = (searchState) => `page=${currentPage(searchState)}&limit=${pageSize(searchState)}`

export const selectors = {
  pagination,
  pageSize,
  currentPage,
  queryString
}
