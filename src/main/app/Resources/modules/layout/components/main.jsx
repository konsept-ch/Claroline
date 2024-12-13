import React, {Fragment} from 'react'
import {PropTypes as T} from 'prop-types'

import {Routes} from '#/main/app/router/components/routes'

import {LayoutSidebar} from '#/main/app/layout/components/sidebar'
import {LayoutToolbar} from '#/main/app/layout/components/toolbar'

import {DesktopMenu} from '#/main/app/layout/sections/desktop/containers/menu'
import {DesktopMain} from '#/main/app/layout/sections/desktop/containers/main'

import {WorkspaceMenu} from '#/main/core/workspace/containers/menu'
import {WorkspaceMain} from '#/main/core/workspace/containers/main'

const LayoutMain = props =>
  <Fragment>
    <div className="app" role="presentation">
      {false && <div className="app-loader" />}

      {props.menuOpened &&
        <Routes
          routes={[
            {
              path: '/desktop/workspaces/open/:slug',
              component: WorkspaceMenu,
              disabled: props.unavailable
            }
          ]}
        />
      }

      <div className="app-content" role="presentation">
        <Routes
          routes={[
            {
              path: '/desktop/workspaces/open/:slug',
              onEnter: (params = {}) => props.openWorkspace(params.slug),
              component: WorkspaceMain,
              disabled: props.unavailable
            }
          ]}
        />
      </div>

      {false && props.authenticated &&
        <LayoutToolbar
          opened={props.sidebar}
          open={props.openSidebar}
        />
      }
    </div>

    {(false && props.authenticated && props.sidebar) &&
      <LayoutSidebar
        close={props.closeSidebar}
      />
    }
  </Fragment>

LayoutMain.propTypes = {
  unavailable: T.bool.isRequired,
  authenticated: T.bool.isRequired,

  openWorkspace: T.func.isRequired,

  menuOpened: T.bool.isRequired,
  toggleMenu: T.func.isRequired,

  sidebar: T.string,
  openSidebar: T.func.isRequired,
  closeSidebar: T.func.isRequired
}

export {
  LayoutMain
}
