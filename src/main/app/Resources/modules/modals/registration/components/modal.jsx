import React from 'react'
import {PropTypes as T} from 'prop-types'
import omit from 'lodash/omit'

import {trans} from '#/main/app/intl/translation'
import {Modal} from '#/main/app/overlays/modal/components/modal'

const RegistrationModal = props =>
  <Modal
    {...omit(props, 'onRegister')}
    title={trans('registration')}
    bsSize="lg"
  >
    <div className="modal-body">
    </div>
  </Modal>

RegistrationModal.propTypes = {
  onRegister: T.func,
  fadeModal: T.func.isRequired
}

export {
  RegistrationModal
}
