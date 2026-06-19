import React, {Fragment} from 'react'
import {PropTypes as T} from 'prop-types'
import get from 'lodash/get'
import classes from 'classnames'

import {url} from '#/main/app/api'
import {trans, now} from '#/main/app/intl'
import {LINK_BUTTON, CALLBACK_BUTTON, MODAL_BUTTON} from '#/main/app/buttons'
import {Button} from '#/main/app/action/components/button'
import {ListData} from '#/main/app/content/list/containers/data'
import {constants as listConst} from '#/main/app/content/list/constants'
import {route} from '#/main/core/user/routing'
import {UserCard} from '#/main/core/user/components/card'
import {MODAL_USERS} from '#/main/core/modals/users'

import {constants} from '#/plugin/cursus/constants'
import {Course as CourseTypes, Session as SessionTypes} from '#/plugin/cursus/prop-types'
import {selectors} from '#/plugin/cursus/tools/trainings/catalog/store/selectors'

function getPresenceDisplay(row, session) {
  const sessionEnded = !!get(session, 'restrictions.dates[1]') && get(session, 'restrictions.dates[1]') < now(false)
  const state = Number(row.presenceState ?? row.state)

  if (constants.REGISTRATION_STATE_PARTICIPATED === state) {
    return {
      label: trans('participation_participated', {}, 'cursus'),
      color: 'success'
    }
  }

  if (sessionEnded) {
    return {
      label: trans('participation_absent', {}, 'cursus'),
      color: 'danger'
    }
  }

  return {
    label: trans('participation_waiting', {}, 'cursus'),
    color: 'warning'
  }
}

const CoursePresences = (props) =>
  <Fragment>
    <ListData
      name={selectors.STORE_NAME + '.sessionPresences'}
      fetch={{
        url: url(['apiv2_cursus_session_list_users', {type: constants.LEARNER_TYPE, id: props.activeSession.id}], {
          hiddenFilters: {
            validated: true
          }
        }),
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
        }, {
          name: 'presenceState',
          type: 'choice',
          label: trans('participation_status', {}, 'cursus'),
          displayed: true,
          sortable: false,
          filterable: false,
          options: {
            choices: {
              [constants.REGISTRATION_STATE_VALIDATED]: trans('participation_waiting', {}, 'cursus'),
              [constants.REGISTRATION_STATE_PARTICIPATED]: trans('participation_participated', {}, 'cursus')
            }
          },
          render: (row) => {
            const presence = getPresenceDisplay(row, props.activeSession)

            return (
              <span className={classes('label', `label-${presence.color}`)}>
                {presence.label}
              </span>
            )
          }
        }
      ]}
      primaryAction={(row) => ({
        type: LINK_BUTTON,
        target: route(row.user)
      })}
      actions={(rows) => [
        {
          name: 'participation',
          type: CALLBACK_BUTTON,
          icon: 'fa fa-fw fa-check',
          label: trans('validate_participation', {}, 'actions'),
          callback: () => props.validateParticipation(props.activeSession.id, rows)
        }
      ]}
      card={(cardProps) => <UserCard {...cardProps} data={cardProps.data.user} />}
      display={{
        current: listConst.DISPLAY_TABLE
      }}
    />

    <Button
      className="btn btn-block btn-emphasis component-container"
      primary={true}
      name={'add_users'}
      type={MODAL_BUTTON}
      label={trans('add_users')}
      modal={[MODAL_USERS, {
        selectAction: (selected) => ({
          type: CALLBACK_BUTTON,
          label: trans('register', {}, 'actions'),
          callback: () => props.addUsers(props.activeSession.id, selected, constants.LEARNER_TYPE)
        })
      }]}
    />
  </Fragment>

CoursePresences.propTypes = {
  path: T.string.isRequired,
  course: T.shape(
    CourseTypes.propTypes
  ).isRequired,
  activeSession: T.shape(
    SessionTypes.propTypes
  ),
  addUsers: T.func.isRequired,
  validateParticipation: T.func.isRequired
}

export {
  CoursePresences
}
