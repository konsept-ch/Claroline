import React from 'react'
import {PropTypes as T} from 'prop-types'

import {trans} from '#/main/app/intl/translation'
import {CALLBACK_BUTTON} from '#/main/app/buttons'

import {ListData} from '#/main/app/content/list/containers/data'

const PendingList = (props) =>
  <ListData
    name={props.name}
    fetch={{
      url: props.url,
      autoload: true
    }}
    actions={(rows) => [
      {
        name: 'confirm',
        type: CALLBACK_BUTTON,
        icon: 'fa fa-fw fa-user-check',
        label: trans('confirm_registration', {}, 'actions'),
        callback: () => props.confirmPending(rows[0].session.id, rows),
        group: trans('management')
      }, {
        name: 'validate',
        type: CALLBACK_BUTTON,
        icon: 'fa fa-fw fa-check',
        label: trans('validate_registration', {}, 'actions'),
        callback: () => props.validatePending(rows[0].session.id, rows),
        group: trans('management')
      }
    ]}
    definition={[
      {
        name: 'session',
        type: 'training_session',
        label: trans('session'),
        displayed: true,
        primary: true,
        sortable: false,
        filterable: false
      }, {
        name: 'user',
        type: 'user',
        label: trans('user'),
        displayed: true,
        sortable: false
      }, {
        name: 'date',
        type: 'date',
        label: trans('date'),
        displayed: true,
        sortable: false,
        filterable: false
      }
    ]}
    selectable={false}
  />

PendingList.propTypes = {
  path: T.string.isRequired,
  name: T.string.isRequired,
  url: T.oneOfType([T.string, T.array]),
  confirmPending: T.func.isRequired,
  validatePending: T.func.isRequired,
}

PendingList.defaultProps = {
  url: ['apiv2_cursus_session_list_pendings']
}

export {
  PendingList
}
