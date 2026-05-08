import React, {Component} from 'react'
import {connect} from 'react-redux'

import {withReducer} from '#/main/app/store/components/withReducer'
import {
  actions as listActions,
  select as listSelect
} from '#/main/app/content/list/store'
import {flattenTree} from '#/main/app/content/tree/utils'

import {reducer, selectors} from '#/main/core/modals/organizations/store'
import {OrganizationsModal as OrganizationsModalComponent} from '#/main/core/modals/organizations/components/modal'

const normalizeSelection = (selected = []) => Array.isArray(selected) ? selected : [selected]

const getSelectionKey = (selected = []) => normalizeSelection(selected)
  .map((selection) => selection && selection.id)
  .filter((id) => undefined !== id && null !== id)
  .join(',')

class OrganizationsModalConnector extends Component {
  constructor(props) {
    super(props)

    this.initialSelectionKey = null
  }

  handleReset() {
    this.initialSelectionKey = null
    this.props.reset()
  }

  componentDidMount() {
    this.syncSelection()
  }

  componentDidUpdate() {
    this.syncSelection()
  }

  syncSelection() {
    const {data, initialSelection, initSelection} = this.props

    if (!initialSelection || 0 === normalizeSelection(initialSelection).length) {
      return
    }

    if (0 === data.length) {
      return
    }

    const selectionKey = getSelectionKey(initialSelection)

    if (this.initialSelectionKey === selectionKey) {
      return
    }

    const selectedRows = flattenTree(data).filter((row) =>
      normalizeSelection(initialSelection).some((selection) => selection && selection.id === row.id)
    )

    if (0 < selectedRows.length) {
      initSelection(selectedRows)
      this.initialSelectionKey = selectionKey
    }
  }

  render() {
    return (
      <OrganizationsModalComponent
        {...this.props}
        reset={() => this.handleReset()}
      />
    )
  }
}

const OrganizationsModal = withReducer(selectors.STORE_NAME, reducer)(
  connect(
    (state) => ({
      data: listSelect.data(listSelect.list(state, selectors.STORE_NAME) || {data: []}) || [],
      selectedIds: listSelect.selected(listSelect.list(state, selectors.STORE_NAME) || {selected: []}) || [],
      selected: (() => {
        const listState = listSelect.list(state, selectors.STORE_NAME) || {data: [], selected: []}
        const selectedIds = listSelect.selected(listState) || []

        return flattenTree(listSelect.data(listState))
          .filter((organization) => -1 !== selectedIds.indexOf(organization.id))
      })()
    }),
    (dispatch) => ({
      initSelection(selected) {
        dispatch(listActions.setSelect(selectors.STORE_NAME, selected))
      },
      reset() {
        dispatch(listActions.resetSelect(selectors.STORE_NAME))
        dispatch(listActions.invalidateData(selectors.STORE_NAME))
      }
    })
  )(OrganizationsModalConnector)
)

export {
  OrganizationsModal
}
