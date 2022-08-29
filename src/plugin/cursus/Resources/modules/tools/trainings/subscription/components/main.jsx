import React from 'react'
import {PropTypes as T} from 'prop-types'

import {Routes} from '#/main/app/router'

import {SubscriptionList} from '#/plugin/cursus/tools/trainings/subscription/components/list'
import {SubscriptionPage} from '#/plugin/cursus/tools/trainings/subscription/containers/page'

const SubscriptionMain = (props) =>
  <Routes
    path={`${props.path}/subscription`}
    routes={[
      {
        path: '/',
        exact: true,
        render: () => (
          <SubscriptionList path={`${props.path}/subscription`} year={props.year} setYear={props.setYear} />
        )
      }, {
        path: '/:id',
        onEnter: (params = {}) => props.open(params.id),
        render: () => (
          <SubscriptionPage path={`${props.path}/subscription`} year={props.year} setYear={props.setYear} />
        )
      }
    ]}
  />

SubscriptionMain.propTypes = {
  path: T.string.isRequired,
  open: T.func.isRequired,
  year: T.number.isRequired,
  setYear: T.func.isRequired
}

export {
  SubscriptionMain
}