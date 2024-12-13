import React, {Component} from 'react'
import {PropTypes as T} from 'prop-types'

import {trans} from '#/main/app/intl/translation'
import {ContentLoader} from '#/main/app/content/components/loader'

class AdministrationMain extends Component {
  componentDidMount() {
    if (!this.props.loaded) {
      this.props.open()
    }
  }

  componentDidUpdate(prevProps) {
    if (!this.props.loaded && prevProps.loaded) {
      this.props.open()
    }
  }

  render() {
    if (!this.props.loaded) {
      return (
        <ContentLoader
          size="lg"
          description={trans('loading', {}, 'administration')}
        />
      )
    }

    return <></>
  }
}

AdministrationMain.propTypes = {
  history: T.shape({
    replace: T.func.isRequired
  }).isRequired,
  loaded: T.bool.isRequired,
  defaultOpening: T.string,
  tools: T.arrayOf(T.shape({

  })),
  open: T.func.isRequired,
  openTool: T.func.isRequired
}

AdministrationMain.defaultProps = {
  tools: []
}

export {
  AdministrationMain
}
