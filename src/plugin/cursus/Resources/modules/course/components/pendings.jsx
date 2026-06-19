import React, {Fragment} from 'react'
import {PropTypes as T} from 'prop-types'

import {url} from '#/main/app/api'
import {trans} from '#/main/app/intl/translation'
import {CALLBACK_BUTTON} from '#/main/app/buttons'

import {constants} from '#/plugin/cursus/constants'
import {Course as CourseTypes, Session as SessionTypes} from '#/plugin/cursus/prop-types'
import {selectors} from '#/plugin/cursus/tools/trainings/catalog/store/selectors'
import {SessionUsers} from '#/plugin/cursus/session/components/users'

const CoursePendings = (props) =>
  <Fragment>
    <SessionUsers
      session={props.activeSession}
      name={selectors.STORE_NAME + '.sessionPending'}
      url={url(['apiv2_cursus_session_list_pending', {id: props.activeSession.id}], {
        allRegistrations: true
      })}
      unregisterUrl={['apiv2_cursus_session_remove_users', {type: constants.LEARNER_TYPE, id: props.activeSession.id}]}
      statusField="validated"
      statusLabel={trans('registration_status', {}, 'cursus')}
      statusChoices={{
        false: trans('registration_pending', {}, 'cursus'),
        true: trans('registration_validated', {}, 'cursus')
      }}
      statusColors={{
        false: 'warning',
        true: 'success'
      }}
      actions={(rows) => [
        {
          name: 'validate',
          type: CALLBACK_BUTTON,
          icon: 'fa fa-fw fa-check',
          label: trans('validate_registration', {}, 'actions'),
          callback: () => props.validatePending(props.activeSession.id, rows.filter(row => !row.validated)),
          displayed: -1 !== rows.findIndex(row => !row.validated),
          group: trans('management')
        }, {
          name: 'refuse',
          type: CALLBACK_BUTTON,
          icon: 'fa fa-fw fa-times',
          label: trans('refuse_registration', {}, 'actions'),
          callback: () => props.refusePending(props.activeSession.id, rows.filter(row => !row.validated)),
          displayed: -1 !== rows.findIndex(row => !row.validated),
          group: trans('management')
        }
      ]}
    />
  </Fragment>

CoursePendings.propTypes = {
  path: T.string.isRequired,
  course: T.shape(
    CourseTypes.propTypes
  ).isRequired,
  activeSession: T.shape(
    SessionTypes.propTypes
  ),
  validatePending: T.func.isRequired,
  refusePending: T.func.isRequired
}

export {
  CoursePendings
}
