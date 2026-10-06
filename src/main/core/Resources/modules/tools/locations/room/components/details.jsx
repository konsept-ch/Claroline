import React from 'react'
import {PropTypes as T} from 'prop-types'
import get from 'lodash/get'

import {trans} from '#/main/app/intl/translation'
import {LINK_BUTTON} from '#/main/app/buttons'
import {Routes} from '#/main/app/router/components/routes'
import {ContentTabs} from '#/main/app/content/components/tabs'
import {Sections, Section} from '#/main/app/content/components/sections'
import {ListData} from '#/main/app/content/list/containers/data'
import {DetailsData} from '#/main/app/content/details/containers/data'

import {Room as RoomTypes} from '#/main/core/tools/locations/prop-types'
import {RoomPage} from '#/main/core/tools/locations/room/containers/page'
import {selectors} from '#/main/core/tools/locations/room/store/selectors'

const RoomAbout = (props) =>
  <DetailsData
    name={selectors.FORM_NAME}
    sections={[
      {
        title: trans('general'),
        primary: true,
        fields: [
          {
            name: 'description',
            type: 'html',
            label: trans('description'),
            required: true
          }, {
            name: 'capacity',
            type: 'number',
            label: trans('capacity'),
            required: true,
            options: {
              min: 0
            }
          }, {
            name: 'location',
            type: 'location',
            label: trans('location')
          }
        ]
      }
    ]}
  >
    <Sections
      level={3}
    >
      <Section
        className="embedded-list-section"
        icon="fa fa-fw fa-calendar"
        title={trans('bookings', {}, 'location')}
      >
        <ListData
          name={`${selectors.FORM_NAME}.events`}
          fetch={{
            url: ['apiv2_location_room_list_event', {room: props.room.id}],
            autoload: true
          }}
          delete={{
            url: ['apiv2_planned_object_delete_bulk'],
            // seulement les évènements et tâches d'agenda : une séance de formation se
            // supprime depuis sa session (elle porte les frais former22_event, supprimés en cascade).
            // Le serveur (AbstractEventVoter) exige d'être admin ou créateur : on n'affiche pas plus.
            displayed: (rows) => props.editable && -1 === rows.findIndex(row =>
              -1 === ['event', 'task'].indexOf(row.meta.type)
              || (!props.isAdmin && get(row, 'meta.creator.id') !== props.currentUserId)
            )
          }}
          definition={[
            {
              name: 'name',
              type: 'string',
              label: trans('name'),
              displayed: true,
              primary: true
            }, {
              name: 'description',
              type: 'html',
              label: trans('description'),
              displayed: true
            }, {
              name: 'start',
              type: 'date',
              label: trans('start_date'),
              displayed: true,
              options: {time: true}
            }, {
              name: 'end',
              type: 'date',
              label: trans('end_date'),
              displayed: true,
              options: {time: true}
            }
          ]}
        />
      </Section>
    </Sections>
  </DetailsData>

RoomAbout.propTypes = {
  room: T.shape(
    RoomTypes.propTypes
  ),
  editable: T.bool.isRequired,
  isAdmin: T.bool.isRequired,
  currentUserId: T.string,
  invalidateBookings: T.func.isRequired
}

const RoomDetails = (props) =>
  <RoomPage
    room={props.room}
  >
    <header className="row content-heading">
      <ContentTabs
        backAction={{
          type: LINK_BUTTON,
          target: `${props.path}/rooms`,
          exact: true
        }}
        sections={[
          {
            name: 'about',
            type: LINK_BUTTON,
            icon: 'fa fa-fw fa-info',
            label: trans('about'),
            target: `${props.path}/rooms/${props.room.id}`,
            exact: true
          }, {
            name: 'planning',
            type: LINK_BUTTON,
            icon: 'fa fa-fw fa-calendar',
            label: trans('planning', {}, 'location'),
            target: `${props.path}/rooms/${props.room.id}/planning`,
            displayed: false
          }
        ]}
      />
    </header>

    <Routes
      path={`${props.path}/rooms/${props.room.id}`}
      routes={[
        {
          path: '/',
          exact: true,
          render: () => (
            <RoomAbout
              room={props.room}
              editable={props.editable}
              isAdmin={props.isAdmin}
              currentUserId={props.currentUserId}
              invalidateBookings={props.invalidateBookings}
            />
          )
        }
      ]}
    />
  </RoomPage>

RoomDetails.propTypes = {
  path: T.string.isRequired,
  room: T.shape(
    RoomTypes.propTypes
  ),
  editable: T.bool.isRequired,
  isAdmin: T.bool.isRequired,
  currentUserId: T.string,
  invalidateBookings: T.func.isRequired
}

export {
  RoomDetails
}
