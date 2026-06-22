import React from 'react'
import {PropTypes as T} from 'prop-types'

import {trans} from '#/main/app/intl/translation'
import {Routes} from '#/main/app/router/components/routes'
import {LINK_BUTTON} from '#/main/app/buttons'
import {ContentTabs} from '#/main/app/content/components/tabs'
import {route as workspaceRoute} from '#/main/core/workspace/routing'

import {SessionList} from '#/plugin/cursus/session/components/list'
import {selectors} from '#/plugin/cursus/tools/trainings/session/store/selectors'

const defaultUrls = {
  active: ['apiv2_cursus_my_sessions_active'],
  ended: ['apiv2_cursus_my_sessions_ended'],
  pending: ['apiv2_cursus_my_sessions_pending']
}

const SessionHistory = (props) =>
  <>
    <header className="row content-heading">
      <ContentTabs
        sections={[
          {
            name: 'current',
            type: LINK_BUTTON,
            label: trans('Actives', {}, 'cursus'),
            target: `${props.path}/registered/`,
            exact: true
          }, {
            name: 'ended',
            type: LINK_BUTTON,
            label: trans('session_ended', {}, 'cursus'),
            target: `${props.path}/registered/ended`
          }, {
            name: 'pending',
            type: LINK_BUTTON,
            label: trans('pending_registrations'),
            target: `${props.path}/registered/pending`
          }
        ]}
      />
    </header>

    <Routes
      path={props.path}
      routes={[
        {
          path: '',
          exact: true,
          onEnter: () => props.invalidateList(),
          render: () => (
            <SessionList
              path={props.sessionPath}
              name={selectors.STORE_NAME}
              url={props.urls.active}
              actions={(rows) => [
                {
                  type: LINK_BUTTON,
                  icon: 'fa fa-fw fa-book',
                  label: trans('open-workspace', {}, 'actions'),
                  target: workspaceRoute(rows[0].workspace),
                  displayed: !!rows[0].workspace,
                  scope: ['object']
                }
              ]}
            />
          )
        }, {
          path: '/registered',
          exact: true,
          onEnter: () => props.invalidateList(),
          render: () => (
            <SessionList
              path={props.sessionPath}
              name={selectors.STORE_NAME}
              url={props.urls.active}
              actions={(rows) => [
                {
                  type: LINK_BUTTON,
                  icon: 'fa fa-fw fa-book',
                  label: trans('open-workspace', {}, 'actions'),
                  target: workspaceRoute(rows[0].workspace),
                  displayed: !!rows[0].workspace,
                  scope: ['object']
                }
              ]}
            />
          )
        }, {
          path: '/registered/ended',
          onEnter: () => props.invalidateList(),
          render: () => (
            <SessionList
              path={props.sessionPath}
              name={selectors.STORE_NAME}
              url={props.urls.ended}
              actions={(rows) => [
                {
                  name: 'open-workspace',
                  type: LINK_BUTTON,
                  icon: 'fa fa-fw fa-book',
                  label: trans('open-workspace', {}, 'actions'),
                  target: workspaceRoute(rows[0].workspace),
                  displayed: !!rows[0].workspace,
                  scope: ['object']
                }
              ]}
            />
          )
        }, {
          path: '/registered/pending',
          onEnter: () => props.invalidateList(),
          render: () => (
            <SessionList
              path={props.sessionPath}
              name={selectors.STORE_NAME}
              url={props.urls.pending}
            />
          )
        }
      ]}
    />
  </>

SessionHistory.propTypes = {
  path: T.string.isRequired,
  sessionPath: T.string.isRequired,
  invalidateList: T.func.isRequired,
  urls: T.shape({
    active: T.oneOfType([T.string, T.array]).isRequired,
    ended: T.oneOfType([T.string, T.array]).isRequired,
    pending: T.oneOfType([T.string, T.array]).isRequired
  })
}

SessionHistory.defaultProps = {
  urls: defaultUrls
}

export {
  SessionHistory
}
