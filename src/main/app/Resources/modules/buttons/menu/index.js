/**
 * Menu button.
 * Opens a contextual menu containing actions.
 */

import {registry} from '#/main/app/buttons/registry'
import {MENU_BUTTON} from '#/main/app/buttons/constants'
import {MenuButton} from '#/main/app/buttons/menu/components/button'

// make the button available for use
registry.add(MENU_BUTTON, MenuButton)

export {
  MENU_BUTTON,
  MenuButton
}
