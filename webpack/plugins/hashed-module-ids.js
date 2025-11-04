const webpack = require('webpack')

/**
 * This plugin will ensure all modules will keep the same name
 * between different builds. This permits to avoid rebuilding
 * some chunks just because a module ids has changed.
 *
 * @see https://webpack.js.org/guides/caching/
 */
const getHashedModuleIdsPlugin = () => {
  if (webpack.ids && webpack.ids.HashedModuleIdsPlugin) {
    return webpack.ids.HashedModuleIdsPlugin
  }

  return webpack.HashedModuleIdsPlugin
}

module.exports = () => new (getHashedModuleIdsPlugin())()
