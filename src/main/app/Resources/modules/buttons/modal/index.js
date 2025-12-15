/**
 * Modal button.
 * Opens a registered modal.
 */

import {registry} from '#/main/app/buttons/registry'
import {MODAL_BUTTON} from '#/main/app/buttons/constants'
import {ModalButton} from '#/main/app/buttons/modal/containers/button'

// make the button available for use
registry.add(MODAL_BUTTON, ModalButton)

export {
  MODAL_BUTTON,
  ModalButton
}
