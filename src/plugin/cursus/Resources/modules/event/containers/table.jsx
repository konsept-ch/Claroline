import {connect} from 'react-redux'

import {actions} from '#/plugin/cursus/event/store'
import {EventTable as EventTableComponent} from '#/plugin/cursus/event/components/table'

const EventTable = connect(
	null,
  	(dispatch) => ({
    	fetchEvents(sessionId, success) {
      		dispatch(actions.fetchEvents(sessionId, success))
    	}
	})
)(EventTableComponent)

export {
  EventTable
}
