// [OPTIMIZATION]
// maybe avoid load all locales and gets only the current one
// it's not really a big deal for now because it's only 5 locales & short objects
const languageLoaders = {
  de: () => import(/* webpackChunkName: "tinymce-lang-de" */ '#/main/core/tinymce/langs/de'),
  es: () => import(/* webpackChunkName: "tinymce-lang-es" */ '#/main/core/tinymce/langs/es'),
  fr: () => import(/* webpackChunkName: "tinymce-lang-fr" */ '#/main/core/tinymce/langs/fr'),
  it: () => import(/* webpackChunkName: "tinymce-lang-it" */ '#/main/core/tinymce/langs/it'),
  nl: () => import(/* webpackChunkName: "tinymce-lang-nl" */ '#/main/core/tinymce/langs/nl')
}

const loadedLanguages = {}

function loadTinymceLanguage(currentLocale) {
  const loader = languageLoaders[currentLocale]

  if (!loader) {
    return Promise.resolve()
  }

  if (!loadedLanguages[currentLocale]) {
    loadedLanguages[currentLocale] = loader()
  }

  return loadedLanguages[currentLocale]
}

export {
  loadTinymceLanguage
}
