import React from 'react'
import {PropTypes as T} from 'prop-types'
import isEmpty from 'lodash/isEmpty'

import {trans} from '#/main/app/intl'
import {LINK_BUTTON} from '#/main/app/buttons'

import {MenuMain} from '#/main/app/layout/menu/containers/main'
import {ToolMenu} from '#/main/core/tool/containers/menu'
import {route as toolRoute} from '#/main/core/tool/routing'

import {getActions} from '#/main/core/workspace/utils'
import {Workspace as WorkspaceTypes} from '#/main/core/workspace/prop-types'

const WorkspaceMenu = (props) => {
  let workspaceActions
  if (!isEmpty(props.workspace)) {
    workspaceActions = getActions([props.workspace], {
      update(workspaces) {
        props.update(workspaces[0])
      },
      delete() {
        props.history.push(toolRoute('workspaces'))
      }
    }, props.basePath, props.currentUser)
  }

  return (
    <MenuMain
      title=""
      backAction={{
        type: LINK_BUTTON,
        icon: 'fa fa-fw fa-angle-double-left',
        label: trans('workspaces'),
        target: toolRoute('workspaces'),
        exact: true
      }}

      tools={[]}
      actions={workspaceActions}
    >
      <ToolMenu
        opened={'tool' === props.section}
        toggle={() => props.changeSection('tool')}
      />
    </MenuMain>
  )
}

WorkspaceMenu.propTypes = {
  history: T.shape({
    push: T.func.isRequired
  }).isRequired,
  basePath: T.string,
  section: T.string,
  workspace: T.shape(
    WorkspaceTypes.propTypes
  )
}

WorkspaceMenu.defaultProps = {
  workspace: {}
}

export {
  WorkspaceMenu
}
