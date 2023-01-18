import React from 'react'
import {PropTypes as T} from 'prop-types'

import {trans} from '#/main/app/intl/translation'
import {LINK_BUTTON} from '#/main/app/buttons'
import {ToolPage} from '#/main/core/tool/containers/page'

import {QuotaForm} from '#/plugin/cursus/quota/containers/form'
import {selectors} from '#/plugin/cursus/tools/trainings/quota/store'

const QuotaCreation = (props) =>
  <ToolPage
    path={[
      {
        type: LINK_BUTTON,
        label: trans('quota', {}, 'cursus'),
        target: props.path
      }, {
        label: trans('new_quota', {}, 'cursus')
      }
    ]}
    title={trans('trainings', {}, 'tools')}
    subtitle={trans('new_quota', {}, 'cursus')}
    primaryAction="add"
    actions={[
      {
        name: 'add',
        type: LINK_BUTTON,
        icon: 'fa fa-fw fa-plus',
        label: trans('add_quota', {}, 'cursus'),
        target: `${props.path}/new`,
        group: trans('management'),
        primary: true
      }
    ]}
  >
    <QuotaForm
      path={props.path}
      name={selectors.FORM_NAME}
    />
  </ToolPage>

QuotaCreation.propTypes = {
  path: T.string.isRequired,
  currentContext: T.shape({
    type: T.oneOf(['desktop']),
    data: T.object
  })
}

export {
  QuotaCreation
}