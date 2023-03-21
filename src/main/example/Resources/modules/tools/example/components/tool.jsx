import React from 'react'

import {trans} from '#/main/app/intl/translation'
import {ToolPage} from '#/main/core/tool/containers/page'
import {Toolbar} from '#/main/app/action/components/toolbar'
import {MODAL_BUTTON, CALLBACK_BUTTON} from '#/main/app/buttons'

import {MODAL_EXAMPLE_ABOUT} from '#/main/example/tools/example/modals/about'

const ExampleTool = () =>
  <ToolPage>
    <Toolbar
      buttonName="btn btn-block"
      actions={[
        {
          className: 'btn-emphasis',
          type: MODAL_BUTTON,
          label: trans('show-info', {}, 'actions'),
          modal: [MODAL_EXAMPLE_ABOUT, {
            name: 'Lorem ipsum dolor sit amet',
            content: 'Lorem ipsum dolor sit amet'
          }],
          primary: true
        }, {
          type: CALLBACK_BUTTON,
          label: trans('select', {}, 'actions'),
          callback: () => alert('coucou')
        }
      ]}
    />
  </ToolPage>

export {
  ExampleTool
}
