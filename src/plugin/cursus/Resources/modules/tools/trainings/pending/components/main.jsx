import React from 'react'
import {PropTypes as T} from 'prop-types'

import {Routes} from '#/main/app/router'

import {PendingList} from '#/plugin/cursus/tools/trainings/pending/components/list'

const PendingMain = (props) =>
  <Routes
    path={`${props.path}/pending`}
    routes={[
      {
        path: '/',
        exact: true,
        render: () => (
          <PendingList path={`${props.path}/pending`} />
        )
      }
    ]}
  />

PendingMain.propTypes = {
  path: T.string.isRequired
}

export {
  PendingMain
}