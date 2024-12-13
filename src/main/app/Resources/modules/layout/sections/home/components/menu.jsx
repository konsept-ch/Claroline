import React from 'react'
import {PropTypes as T} from 'prop-types'

const HomeMenu = (props) => <></>

HomeMenu.propTypes = {
  homeType: T.string.isRequired,
  unavailable: T.bool.isRequired,
  selfRegistration: T.bool.isRequired,
  authenticated: T.bool.isRequired
}

export {
  HomeMenu
}
