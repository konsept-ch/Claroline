const pluginRegistry = {
  'advanced-fullscreen': () => import(/* webpackChunkName: "tinymce-plugin-advanced-fullscreen" */ '#/main/core/tinymce/plugins/advanced-fullscreen'),
  'advanced-toolbar': () => import(/* webpackChunkName: "tinymce-plugin-advanced-toolbar" */ '#/main/core/tinymce/plugins/advanced-toolbar'),
  anchor: () => import(/* webpackChunkName: "tinymce-plugin-anchor" */ 'tinymce/plugins/anchor'),
  autolink: () => import(/* webpackChunkName: "tinymce-plugin-autolink" */ 'tinymce/plugins/autolink'),
  autoresize: () => import(/* webpackChunkName: "tinymce-plugin-autoresize" */ 'tinymce/plugins/autoresize'),
  advlist: () => import(/* webpackChunkName: "tinymce-plugin-advlist" */ 'tinymce/plugins/advlist'),
  charmap: () => import(/* webpackChunkName: "tinymce-plugin-charmap" */ 'tinymce/plugins/charmap'),
  code: () => import(/* webpackChunkName: "tinymce-plugin-code" */ 'tinymce/plugins/code'),
  codemirror: () => import(/* webpackChunkName: "tinymce-plugin-codemirror" */ '#/main/core/tinymce/plugins/codemirror'),
  contextmenu: () => import(/* webpackChunkName: "tinymce-plugin-contextmenu" */ 'tinymce/plugins/contextmenu'),
  'file-upload': () => import(/* webpackChunkName: "tinymce-plugin-file-upload" */ '#/main/core/tinymce/plugins/file-upload'),
  fullscreen: () => import(/* webpackChunkName: "tinymce-plugin-fullscreen" */ 'tinymce/plugins/fullscreen'),
  image: () => import(/* webpackChunkName: "tinymce-plugin-image" */ 'tinymce/plugins/image'),
  insertdatetime: () => import(/* webpackChunkName: "tinymce-plugin-insertdatetime" */ 'tinymce/plugins/insertdatetime'),
  link: () => import(/* webpackChunkName: "tinymce-plugin-link" */ 'tinymce/plugins/link'),
  lists: () => import(/* webpackChunkName: "tinymce-plugin-lists" */ 'tinymce/plugins/lists'),
  media: () => import(/* webpackChunkName: "tinymce-plugin-media" */ 'tinymce/plugins/media'),
  mentions: () => import(/* webpackChunkName: "tinymce-plugin-mentions" */ '#/main/core/tinymce/plugins/mentions'),
  paste: () => import(/* webpackChunkName: "tinymce-plugin-paste" */ 'tinymce/plugins/paste'),
  preview: () => import(/* webpackChunkName: "tinymce-plugin-preview" */ 'tinymce/plugins/preview'),
  'resource-picker': () => import(/* webpackChunkName: "tinymce-plugin-resource-picker" */ '#/main/core/tinymce/plugins/resource-picker'),
  searchreplace: () => import(/* webpackChunkName: "tinymce-plugin-searchreplace" */ 'tinymce/plugins/searchreplace'),
  table: () => import(/* webpackChunkName: "tinymce-plugin-table" */ 'tinymce/plugins/table'),
  textcolor: () => import(/* webpackChunkName: "tinymce-plugin-textcolor" */ 'tinymce/plugins/textcolor'),
  visualblocks: () => import(/* webpackChunkName: "tinymce-plugin-visualblocks" */ 'tinymce/plugins/visualblocks'),
  wordcount: () => import(/* webpackChunkName: "tinymce-plugin-wordcount" */ 'tinymce/plugins/wordcount')
}

const pluginNames = Object.keys(pluginRegistry)
const pluginCache = new Map()

function importPlugin(name) {
  const loader = pluginRegistry[name]

  if (!loader) {
    return Promise.resolve(null)
  }

  if (!pluginCache.has(name)) {
    pluginCache.set(
      name,
      loader()
        .then(() => name)
        .catch(error => {
          pluginCache.delete(name)
          throw error
        })
    )
  }

  return pluginCache.get(name)
}

function loadTinymcePlugins(requested = pluginNames) {
  const normalized = []

  requested.forEach(name => {
    if (name && normalized.indexOf(name) === -1) {
      normalized.push(name)
    }
  })

  const known = normalized.filter(name => pluginRegistry[name])

  if (known.length === 0) {
    return Promise.resolve(normalized)
  }

  return Promise.all(known.map(importPlugin)).then(() => normalized)
}

export {
  loadTinymcePlugins,
  pluginNames,
  pluginRegistry
}
