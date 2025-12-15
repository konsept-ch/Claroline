/**
 * Confirm modal.
 * Displays a modal to request a user confirmation.
 */

import {registry} from '#/main/app/modals/registry'
import {MODAL_CONFIRM} from '#/main/app/modals/confirm/constants'
import {ConfirmModal} from '#/main/app/modals/confirm/components/modal'

// make the modal available for use
registry.add(MODAL_CONFIRM, ConfirmModal)

export {
  MODAL_CONFIRM
}
