import React from 'react'
import {PropTypes as T} from 'prop-types'

import {trans} from '#/main/app/intl/translation'
import {LINK_BUTTON} from '#/main/app/buttons'
import {ToolPage} from '#/main/core/tool/containers/page'

import {SessionHistory} from '#/plugin/cursus/tools/trainings/session/components/history'

const SessionMain = (props) =>
  <ToolPage
    path={[{
      type: LINK_BUTTON,
      label: trans('my_courses', {}, 'cursus'),
      target: `${props.path}/registered`
    }]}
    subtitle={trans('my_courses', {}, 'cursus')}
  >
    <SessionHistory
      path={props.path}
      sessionPath={`${props.path}/catalog`}
      invalidateList={props.invalidateList}
    />
  </ToolPage>

SessionMain.propTypes = {
  path: T.string.isRequired,
  invalidateList: T.func.isRequired
}

export {
  SessionMain
}
