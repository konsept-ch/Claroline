/**
 * Modal used to duplicate a cursus training.
 */

import {registry} from '#/main/app/modals/registry'

import {DuplicateCourseModal} from '#/plugin/cursus/modals/duplicate/components/modal'

const MODAL_DUPLICATE_COURSE = 'MODAL_DUPLICATE_COURSE'

registry.add(MODAL_DUPLICATE_COURSE, DuplicateCourseModal)

export {
  MODAL_DUPLICATE_COURSE
}
