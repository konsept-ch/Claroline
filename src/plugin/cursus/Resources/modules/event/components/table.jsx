import React, {useEffect, useState} from 'react'
import {PropTypes as T} from 'prop-types'

import {trans} from '#/main/app/intl/translation'
import {displayDate} from '#/main/app/intl/date'
import {ContentLoader} from '#/main/app/content/components/loader'

const EventTable = (props) => {
	const [loading, setLoading] = useState(true)
	const [events, setEvents] = useState([])

	useEffect(() => {
		props.fetchEvents(props.sessionId, (response) => {
			setEvents(response.data)
			setLoading(false)
		})
	}, [])

	return loading ? <ContentLoader size="sm" /> : (
		events.length == 0 ? <h4>Aucunes séances disponibles</h4> : (
    		<table className="table table-striped table-bordered">
    			<thead>
        			<tr>
        				<th>{trans('name')}</th>
        				<th>{trans('location')}</th>
        				<th>{trans('start_date')}</th>
        				<th>{trans('end_date')}</th>
        			</tr>
    			</thead>
    			<tbody>
					{events.map(event => (
        				<tr key={event.id}>
        					<td>{event.name}</td>
        					<td>{event.location?.name || trans('event_location_undefined', {}, 'cursus')}</td>
        					<td>{displayDate(event.start, false, true)}</td>
        					<td>{displayDate(event.end, false, true)}</td>
        				</tr>
					))}
    			</tbody>
    		</table>
		)
	)
}

EventTable.propTypes = {
	sessionId: T.string.isRequired
}

export {
	EventTable
}