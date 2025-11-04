const pluginNames = [
  'advanced-fullscreen',
  'advanced-toolbar',
  'anchor',
  'autolink',
  'autoresize',
  'advlist',
  'charmap',
  'code',
  'codemirror',
  'contextmenu',
  'file-upload',
  'fullscreen',
  'image',
  'insertdatetime',
  'link',
  'lists',
  'media',
  'mentions',
  'paste',
  'preview',
  'resource-picker',
  'searchreplace',
  'table',
  'textcolor',
  'visualblocks',
  'wordcount'
]

const pluginLoaders = [
  () => import(/* webpackChunkName: "tinymce-plugins" */ '#/main/core/tinymce/plugins/advanced-fullscreen'),
  () => import(/* webpackChunkName: "tinymce-plugins" */ '#/main/core/tinymce/plugins/advanced-toolbar'),
  () => import(/* webpackChunkName: "tinymce-plugins" */ 'tinymce/plugins/anchor'),
  () => import(/* webpackChunkName: "tinymce-plugins" */ 'tinymce/plugins/autolink'),
  () => import(/* webpackChunkName: "tinymce-plugins" */ 'tinymce/plugins/autoresize'),
  () => import(/* webpackChunkName: "tinymce-plugins" */ 'tinymce/plugins/advlist'),
  () => import(/* webpackChunkName: "tinymce-plugins" */ 'tinymce/plugins/charmap'),
  () => import(/* webpackChunkName: "tinymce-plugins" */ 'tinymce/plugins/code'),
  () => import(/* webpackChunkName: "tinymce-plugins" */ '#/main/core/tinymce/plugins/codemirror'),
  () => import(/* webpackChunkName: "tinymce-plugins" */ 'tinymce/plugins/contextmenu'),
  () => import(/* webpackChunkName: "tinymce-plugins" */ '#/main/core/tinymce/plugins/file-upload'),
  () => import(/* webpackChunkName: "tinymce-plugins" */ 'tinymce/plugins/fullscreen'),
  () => import(/* webpackChunkName: "tinymce-plugins" */ 'tinymce/plugins/image'),
  () => import(/* webpackChunkName: "tinymce-plugins" */ 'tinymce/plugins/insertdatetime'),
  () => import(/* webpackChunkName: "tinymce-plugins" */ 'tinymce/plugins/link'),
  () => import(/* webpackChunkName: "tinymce-plugins" */ 'tinymce/plugins/lists'),
  () => import(/* webpackChunkName: "tinymce-plugins" */ 'tinymce/plugins/media'),
  () => import(/* webpackChunkName: "tinymce-plugins" */ '#/main/core/tinymce/plugins/mentions'),
  () => import(/* webpackChunkName: "tinymce-plugins" */ 'tinymce/plugins/paste'),
  () => import(/* webpackChunkName: "tinymce-plugins" */ 'tinymce/plugins/preview'),
  () => import(/* webpackChunkName: "tinymce-plugins" */ '#/main/core/tinymce/plugins/resource-picker'),
  () => import(/* webpackChunkName: "tinymce-plugins" */ 'tinymce/plugins/searchreplace'),
  () => import(/* webpackChunkName: "tinymce-plugins" */ 'tinymce/plugins/table'),
  () => import(/* webpackChunkName: "tinymce-plugins" */ 'tinymce/plugins/textcolor'),
  () => import(/* webpackChunkName: "tinymce-plugins" */ 'tinymce/plugins/visualblocks'),
  () => import(/* webpackChunkName: "tinymce-plugins" */ 'tinymce/plugins/wordcount')
]

let pluginsPromise = null

function loadTinymcePlugins() {
  if (!pluginsPromise) {
    pluginsPromise = Promise.all(pluginLoaders.map(load => load())).then(() => pluginNames)
  }

  return pluginsPromise
}

export {
  loadTinymcePlugins,
  pluginNames
}
