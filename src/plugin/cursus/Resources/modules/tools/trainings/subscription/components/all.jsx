import React, {Component} from 'react'
import {PropTypes as T} from 'prop-types'

import classes from 'classnames'
import {constants} from '#/plugin/cursus/constants'
import {getSubscriptionStatus} from '#/plugin/cursus/utils'
import {trans} from '#/main/app/intl/translation'
import {MODAL_SUBSCRIPTION_STATUS} from '#/plugin/cursus/subscription/modals/status'
import {MODAL_BUTTON} from '#/main/app/buttons'
import {MODAL_SUBSCRIPTION_ABOUT} from '#/plugin/cursus/subscription/modals/about'
import {ListData} from '#/main/app/content/list/containers/data'

import {SubscriptionCard} from '#/plugin/cursus/subscription/components/card'

class SubscriptionAll extends Component
{
  constructor(props) {
    super(props)
  }
  
  componentWillReceiveProps() {
    console.log('TEST')
  }

  render() {
    return (<ListData
      name={this.props.name}
      fetch={{
        url: this.props.url,
        autoload: true
      }}
      primaryAction={(row) => ({
        name: 'about',
        type: MODAL_BUTTON,
        icon: 'fa fa-fw fa-info',
        label: trans('show-info', {}, 'actions'),
        modal: [MODAL_SUBSCRIPTION_ABOUT, {
          subscription: row
        }],
        scope: ['object']
      })}
      actions={(row) => [
        {
          name: 'edit',
          type: MODAL_BUTTON,
          icon: 'fa fa-fw fa-pencil',
          label: trans('edit', {}, 'actions'),
          modal: [MODAL_SUBSCRIPTION_STATUS, {
            changeStatus: (status) => {
              this.props.setSubscriptionStatus(row[0].id, status)
            }
          }]
        }
      ]}
      definition={[
        {
          name: 'session.course.name',
          alias: 'course',
          type: 'string',
          label: trans('course', {}, 'cursus'),
          displayed: true,
          primary: true,
          sortable: false
        }, {
          name: 'session.name',
          alias: 'session',
          type: 'string',
          label: trans('session', {}, 'cursus'),
          displayed: true,
          filterable: false,
          sortable: false
        }, {
          name: 'user.name',
          alias: 'user',
          type: 'string',
          label: trans('user', {}, 'cursus'),
          displayed: true,
          filterable: false,
          sortable: false
        }, {
          name: 'date',
          alias: 'date',
          type: 'date',
          label: trans('registered', {}, 'cursus'),
          displayed: true,
          filterable: false,
          sortable: false
        }, {
          name: 'status',
          type: 'choice',
          label: trans('status'),
          displayed: true,
          options: {
            choices: constants.SUBSCRIPTION_STATUSES
          },
          render: (row) => (
            <span className={classes('label', `label-${constants.SUBSCRIPTION_STATUS_COLORS[getSubscriptionStatus(row)]}`)}>
              {getSubscriptionStatus(row)}
            </span>
          )
        }
      ]}
      card={SubscriptionCard}
    />)
  }
}

SubscriptionAll.propTypes = {
  path: T.string.isRequired,
  name: T.string.isRequired,
  url: T.oneOfType([T.string, T.array]).isRequired,
  setSubscriptionStatus: T.func.isRequired
}

export {
  SubscriptionAll
}
