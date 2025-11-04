let themePromise = null

function loadTinymceTheme() {
  if (!themePromise) {
    themePromise = import(/* webpackChunkName: "tinymce-theme" */ 'tinymce/themes/modern/theme')
  }

  return themePromise
}

export {
  loadTinymceTheme
}
