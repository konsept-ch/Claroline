import {connect} from 'react-redux'

import {actions} from '#/plugin/cursus/event/store'
import {EventTable as EventTableComponent} from '#/plugin/cursus/event/components/table'

const mapDispatch = (dispatch) => ({
  fetchEvents(sessionId, success) {
    dispatch(actions.fetchEvents(sessionId, success))
  }
})

const EventTable = connect(
  null,
  mapDispatch
)(EventTableComponent)

export {
  EventTable
}
