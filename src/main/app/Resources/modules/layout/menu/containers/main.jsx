import {connect} from 'react-redux'

import {MenuMain as MenuMainComponent} from '#/main/app/layout/menu/components/main'
import {actions, selectors} from '#/main/app/layout/menu/store'
import {selectors as securitySelectors} from '#/main/app/security/store'
import {selectors as toolSelectors} from '#/main/core/tool/store'

const MenuMain =
  connect(
    (state) => ({
      untouched: selectors.untouched(state),
      section: selectors.openedSection(state),
      administration: securitySelectors.hasAdministration(state),
      path: toolSelectors.path(state)
    }),
    (dispatch) => ({
      close() {
        dispatch(actions.close())
      },
      changeSection(section) {
        dispatch(actions.changeSection(section))
      }
    })
  )(MenuMainComponent)

export {
  MenuMain
}