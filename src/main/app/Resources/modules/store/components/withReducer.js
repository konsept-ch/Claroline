import React, {useContext, useLayoutEffect, useRef, useState} from 'react'
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
      const injectedRef = useRef(false)
      const [ready, setReady] = useState(false)

      // Inject reducer after mount to avoid triggering store changes during render
      useLayoutEffect(() => {
        if (!injectedRef.current) {
          store.injectReducer(key, reducer)
          injectedRef.current = true
          setReady(true)
        }
      }, [store])

      if (!ready) {
        return null
      }

      return <WrappedComponent {...props} />
    }

    WithReducer.displayName = `WithReducer(${WrappedComponent.displayName || WrappedComponent.name || 'Component'})`

    return WithReducer
  }
}

export {
  withReducer
}
