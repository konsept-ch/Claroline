import React from 'react'
import {PropTypes as T} from 'prop-types'

import {Routes} from '#/main/app/router'

import {CatalogMain} from '#/plugin/cursus/tools/trainings/catalog/containers/main'
import {SessionMain} from '#/plugin/cursus/tools/trainings/session/containers/main'
import {EventMain} from '#/plugin/cursus/tools/trainings/event/containers/main'
import {QuotaMain} from '#/plugin/cursus/tools/trainings/quota/containers/main'
import {SubscriptionMain} from '#/plugin/cursus/tools/trainings/subscription/containers/main'
import {RegistrationMain} from '#/plugin/cursus/tools/trainings/registration/components/main'

const TrainingsTool = (props) =>
  <Routes
    path={props.path}
    redirect={[
      {from: '/', exact: true, to: '/catalog'}
    ]}
    routes={[
      {
        path: '/catalog',
        component: CatalogMain
      }, {
        path: '/registered',
        component: SessionMain,
        disabled: !props.authenticated
      }, {
        path: '/events',
        component: EventMain
      }, {
        path: '/quota',
        component: QuotaMain,
        disabled: !props.canManageQuotas
      }, {
        path: '/subscription',
        component: SubscriptionMain,
        disabled: !props.canValidateSubscriptions
      }, {
        path: '/registrations/emcc',
        exact: true,
        onEnter: () => props.invalidateRegistrationList(),
        render: () =>  <RegistrationMain path={`${props.path}/registrations/emcc`} tag="emcc" />,
        disabled: !props.canValidateEmcc
      }, {
        path: '/registrations/pci',
        exact: true,
        onEnter: () => props.invalidateRegistrationList(),
        render: () => <RegistrationMain path={`${props.path}/registrations/pci`} tag="pci" />,
        disabled: !props.canValidatePci
      }
    ]}
  />

TrainingsTool.propTypes = {
  path: T.string.isRequired,
  authenticated: T.bool.isRequired,
  canManageQuotas: T.bool.isRequired,
  canValidateSubscriptions: T.bool.isRequired,
  canValidateEmcc: T.bool.isRequired,
  canValidatePci: T.bool.isRequired,
  invalidateRegistrationList: T.func.isRequired
}

export {
  TrainingsTool
}