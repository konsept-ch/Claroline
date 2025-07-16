import React, {Fragment} from 'react'
import {PropTypes as T} from 'prop-types'
import classes from 'classnames'

import {trans} from '#/main/app/intl/translation'
import {hasPermission} from '#/main/app/security'
import {Button} from '#/main/app/action/components/button'
import {ListData} from '#/main/app/content/list/containers/data'
import {constants as listConst} from '#/main/app/content/list/constants'
import {UserCard} from '#/main/core/user/components/card'

import {Session as SessionTypes} from '#/plugin/cursus/prop-types'
import {constants} from '#/plugin/cursus/constants'

const SessionUsers = (props) =>
  <Fragment>
    <ListData
      name={props.name}
      fetch={{
        url: props.url,
        autoload: true
      }}
      delete={{
        url: props.unregisterUrl,
        label: trans('cancel', {}, 'actions'),
        displayed: () => hasPermission('register', props.session)
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
        name: 'status',
        type: 'choice',
        label: trans('status'),
        displayed: true,
        sortable: false,
        filterable: false,
        render: (row) => (
          <span className={classes('label', `label-${constants.REGISTRATION_STATE_COLORS[row.state]}`)}>
            {constants.REGISTRATION_STATES[row.state]}
          </span>
        )
        }
      ]}
      primaryAction={props.primaryAction}
      actions={props.actions}
      card={(cardProps) => <UserCard {...cardProps} data={cardProps.data.user} />}
      display={{
        current: listConst.DISPLAY_TABLE
      }}
    />

    {props.add && hasPermission('register', props.session) &&
      <Button
        className="btn btn-block btn-emphasis component-container"
        primary={true}
        {...props.add}
      />
    }
  </Fragment>

SessionUsers.propTypes = {
  session: T.shape(
    SessionTypes.propTypes
  ).isRequired,
  name: T.string.isRequired,
  url: T.oneOfType([T.string, T.array]).isRequired,
  unregisterUrl: T.oneOfType([T.string, T.array]).isRequired,
  primaryAction: T.func,
  actions: T.func,
  add: T.shape({
    // action types
  })
}

export {
  SessionUsers
}
