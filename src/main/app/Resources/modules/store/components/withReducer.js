import React, {useContext, useEffect, useState} from 'react'
import {ReactReduxContext} from 'react-redux'

/**
 * HOC permitting to dynamically append the reducer needed by a container.
 *
 * @param {string} key
 * @param {object} reducer
 *
 * @return {func}
 */
function withReducer(key, reducer) {
  return function appendReducers(WrappedComponent) {
    const WithReducer = (props) => {
      const {store} = useContext(ReactReduxContext)
      const [ready, setReady] = useState(false)

      useEffect(() => {
        if (!store) {
          return
        }

        // mount the requested reducer after the first render to avoid
        // dispatching store updates while React is still rendering.
        store.injectReducer(key, reducer)
        setReady(true)
      }, [store])

      if (!ready) {
        return null
      }

      return (
        <WrappedComponent {...props} />
      )
    }

    WithReducer.displayName = `WithReducer(${WrappedComponent.displayName || WrappedComponent.name || 'Component'})`

    return WithReducer
  }
}

export {
  withReducer
}
