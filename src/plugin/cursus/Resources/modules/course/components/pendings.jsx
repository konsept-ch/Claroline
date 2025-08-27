import React, {Fragment} from 'react'
import {PropTypes as T} from 'prop-types'
import classes from 'classnames'

import {trans} from '#/main/app/intl/translation'
import {LINK_BUTTON, CALLBACK_BUTTON, MODAL_BUTTON} from '#/main/app/buttons'
import {Button} from '#/main/app/action/components/button'
import {ListData} from '#/main/app/content/list/containers/data'
import {constants as listConst} from '#/main/app/content/list/constants'
import {route} from '#/main/core/user/routing'
import {UserCard} from '#/main/core/user/components/card'
import {MODAL_USERS} from '#/main/core/modals/users'

import {constants} from '#/plugin/cursus/constants'
import {isFull} from '#/plugin/cursus/utils'
import {Course as CourseTypes, Session as SessionTypes} from '#/plugin/cursus/prop-types'
import {selectors} from '#/plugin/cursus/tools/trainings/catalog/store/selectors'

const CoursePendings = (props) =>
  <Fragment>
    <ListData
      name={selectors.STORE_NAME+'.sessionUsers'}
      fetch={{
        url: ['apiv2_cursus_session_list_users', {type: constants.LEARNER_TYPE, id: props.activeSession.id}],
        autoload: true
      }}
      delete={{
        url: ['apiv2_cursus_session_remove_users', {type: constants.LEARNER_TYPE, id: props.activeSession.id}],
        label: trans('cancel', {}, 'actions')
      }}
      definition={[
        {
          name: 'user',
          type: 'user',
          label: trans('user'),
          displayed: true
        }, {
          name: 'organization',
          type: 'string',
          label: trans('organization'),
          displayed: true,
          sortable: false,
          filterable: false
        }, {
          name: 'user.email',
          type: 'email',
          label: trans('email'),
          displayed: true,
          sortable: false,
          filterable: false
        }, {
          name: 'date',
          type: 'date',
          label: trans('registration_date', {}, 'cursus'),
          options: {time: true},
          displayed: true
        }, {
          name: 'userDisabled',
          label: trans('user_disabled'),
          type: 'boolean',
          displayable: false,
          sortable: false,
          filterable: true
        }
      ]}
      primaryAction={(row) => ({
        type: LINK_BUTTON,
        target: route(row.user)
      })}
      actions={(rows) => [
        {
          name: 'validate',
          type: CALLBACK_BUTTON,
          icon: 'fa fa-fw fa-check',
          label: trans('validate_registration', {}, 'actions'),
          callback: () => props.validatePending(props.activeSession.id, rows),
          disabled: isFull(props.activeSession),
          group: trans('management')
        }, {
          name: 'refuse',
          type: CALLBACK_BUTTON,
          icon: 'fa fa-fw fa-times',
          label: trans('refuse_registration', {}, 'actions'),
          callback: () => props.refusePending(props.activeSession.id, rows),
          disabled: isFull(props.activeSession),
          group: trans('management')
        }
      ]}
      card={(cardProps) => <UserCard {...cardProps} data={cardProps.data.user} />}
      display={{
        current: listConst.DISPLAY_TABLE
      }}
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
