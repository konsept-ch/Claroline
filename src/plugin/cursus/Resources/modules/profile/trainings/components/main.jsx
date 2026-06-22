import React, {Component} from 'react'
import {PropTypes as T} from 'prop-types'

import {trans} from '#/main/app/intl/translation'
import {Button} from '#/main/app/action/components/button'
import {LINK_BUTTON} from '#/main/app/buttons'
import {route as sessionRoute} from '#/plugin/cursus/routing'

import {User as UserTypes} from '#/main/core/user/prop-types'

class ProfileTrainingsMain extends Component {
  constructor(props) {
    super(props)

    this.state = {
      loading: true,
      active: [],
      ended: [],
      pending: []
    }
  }

  componentDidMount() {
    this.loadTrainings()
  }

  componentDidUpdate(prevProps) {
    if (prevProps.user && this.props.user && prevProps.user.username !== this.props.user.username) {
      this.loadTrainings()
    }
  }

  loadTrainings() {
    const username = this.props.user && this.props.user.username
    if (!username) {
      this.setState({
        loading: false,
        active: [],
        ended: [],
        pending: []
      })
      return
    }

    this.setState({loading: true})

    const baseUrl = `/apiv2/users/${encodeURIComponent(username)}/sessions`

    Promise.all([
      fetch(`${baseUrl}/active`).then(response => response.json()),
      fetch(`${baseUrl}/ended`).then(response => response.json()),
      fetch(`${baseUrl}/pending`).then(response => response.json())
    ]).then(([active, ended, pending]) => {
      this.setState({
        loading: false,
        active: active.data || [],
        ended: ended.data || [],
        pending: pending.data || []
      })
    }).catch(() => {
      this.setState({
        loading: false,
        active: [],
        ended: [],
        pending: []
      })
    })
  }

  renderSection(title, items, emptyLabel) {
    if (0 === items.length) {
      return null
    }

    return (
      <section style={{marginBottom: 30}}>
        <h3 style={{fontSize: 22, margin: '0 0 15px'}}>{title}</h3>
        <div className="list-group">
          {items.map(item => (
            <Button
              key={item.id}
              type={LINK_BUTTON}
              target={sessionRoute('/desktop/trainings/catalog', item.course, item)}
              className="list-group-item"
            >
              <strong>{item.name}</strong>
              {item.code &&
                <span style={{marginLeft: 8, color: '#666'}}>({item.code})</span>
              }
            </Button>
          ))}
        </div>
      </section>
    )
  }

  render() {
    const total = this.state.active.length + this.state.ended.length + this.state.pending.length

    return (
      <div style={{paddingTop: 20}}>
        {this.state.loading &&
          <div style={{margin: '15px 0 20px'}}>
            {trans('loading', {}, 'resource')}
          </div>
        }

        {!this.state.loading && 0 === total &&
          <div className="alert alert-info" style={{margin: '15px 0 20px'}}>
            {trans('no_course', {}, 'cursus')}
          </div>
        }

        {!this.state.loading && this.renderSection(trans('Actives', {}, 'cursus'), this.state.active)}
        {!this.state.loading && this.renderSection(trans('session_ended', {}, 'cursus'), this.state.ended)}
        {!this.state.loading && this.renderSection(trans('pending_registrations'), this.state.pending)}
      </div>
    )
  }
}

ProfileTrainingsMain.propTypes = {
  path: T.string.isRequired,
  user: T.shape(UserTypes.propTypes),
  invalidateList: T.func
}

ProfileTrainingsMain.defaultProps = {
  invalidateList: () => {}
}

export {
  ProfileTrainingsMain
}
