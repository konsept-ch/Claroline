/* global window */

let tinymcePromise = null

/**
 * Lazily load TinyMCE core and expose the shared instance.
 *
 * @returns {Promise<object>}
 */
function loadTinymce() {
  if (!tinymcePromise) {
    tinymcePromise = import(/* webpackChunkName: "tinymce-core" */ 'tinymce/tinymce').then((module) => {
      const loadedTinymce = module?.tinymce || module?.default || module

      if (!window.tinymce) {
        window.tinymce = loadedTinymce
      }

      return window.tinymce
    })
  }

  return tinymcePromise
}

export {
  loadTinymce
}
