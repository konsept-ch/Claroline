import React from 'react'
import {PropTypes as T} from 'prop-types'
import {trans} from '#/main/app/intl/translation'
import {LINK_BUTTON} from '#/main/app/buttons'
import {ToolPage} from '#/main/core/tool/containers/page'
import {PendingList as PendingDataList} from '#/plugin/cursus/pending/containers/list'
import {selectors} from '#/plugin/cursus/tools/trainings/pending/store/selectors'

const PendingList = (props) =>
  <ToolPage
    path={[{
      type: LINK_BUTTON,
      label: trans('pendings', {}, 'cursus'),
      target: props.path
    }]}
    subtitle={trans('pendings', {}, 'cursus')}
  >
    <PendingDataList
      name={selectors.LIST_NAME}
      path={props.path}
    />
  </ToolPage>

PendingList.propTypes = {
  path: T.string.isRequired
}

export {
  PendingList
}
