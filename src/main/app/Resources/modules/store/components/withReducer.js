import React, {useContext, useEffect} from 'react'
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

      useEffect(() => {
        // mount the requested reducer into the current redux store
        store.injectReducer(key, reducer)
      }, [store])

      // just render the original component and forward its props
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
