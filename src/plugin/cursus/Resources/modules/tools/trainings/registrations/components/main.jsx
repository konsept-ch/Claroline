import React from 'react'
import {PropTypes as T} from 'prop-types'
import classes from 'classnames'

import {trans} from '#/main/app/intl/translation'
import {LINK_BUTTON} from '#/main/app/buttons'
import {ListData} from '#/main/app/content/list/containers/data'
import {ToolPage} from '#/main/core/tool/containers/page'
import {UserCard} from '#/main/core/user/components/card'
import {selectors} from '#/plugin/cursus/tools/trainings/quota/store/selectors'
import {constants} from '#/plugin/cursus/constants'

const RegistrationsMain = (props) =>
  <ToolPage
    path={[{
      type: LINK_BUTTON,
      label: trans('registrations', {}, 'cursus'),
      target: props.path
    }]}
    subtitle={trans(`validation_${props.tag}`, {}, 'cursus')}
  >
    <ListData
      name={selectors.LIST_NAME}
      fetch={{
        url: ['apiv2_cursus_registration', { tag: props.tag }],
        autoload: true
      }}
      primaryAction={(row) => ({
        type: LINK_BUTTON,
        label: trans('open', {}, 'actions'),
        target: `${props.path}/${row.id}`
      })}
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
      card={(cardProps) => <UserCard {...cardProps} data={cardProps.data.user} />}
      selectable={false}
    />
  </ToolPage>

RegistrationsMain.propTypes = {
  path: T.string.isRequired
}

export {
  RegistrationsMain
}
