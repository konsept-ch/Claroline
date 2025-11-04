import invariant from 'invariant'

import {loadTinymcePlugins, pluginNames as defaultPluginNames} from '#/main/core/tinymce/plugins'
import {loadTinymceLanguage} from '#/main/core/tinymce/langs'
import {loadTinymceTheme} from '#/main/core/tinymce/themes'

import {url} from '#/main/app/api'
import {makeId} from '#/main/core/scaffolding/id'
import {trans} from '#/main/app/intl/translation'
import {locale} from '#/main/app/intl/locale'
import {asset, param, theme} from '#/main/app/config'
import {url as urlValidator} from '#/main/app/data/types/validators'
import {getExternalPlugins} from '#/main/core/tinymce/plugins/external-plugins'

const INSERT_BUTTON_ITEMS = 'resource-picker file-upload link media image | anchor charmap inserttable insertdatetime'
const TOOLBAR_1 = 'advanced-toolbar | insert | undo redo | formatselect | bold italic underline | forecolor'
const TOOLBAR_2 = 'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat preview code'

const TOOLBAR_PLUGIN_MAP = {
  forecolor: ['textcolor'],
  inserttable: ['table'],
  bullist: ['lists', 'advlist'],
  numlist: ['lists', 'advlist'],
  indent: ['lists'],
  outdent: ['lists'],
  link: ['link', 'autolink']
}

const CONFIG_DEPENDENT_PLUGINS = ['autoresize', 'paste', 'codemirror']
const INTERNAL_PLUGIN_FLAG_PATH = 'tinymce.internal_plugins'

let configPromise = null

function normalizePluginList(value) {
  if (!value) {
    return null
  }

  const sanitize = (list) => {
    const normalized = list
      .filter(item => typeof item === 'string')
      .map(item => item.trim())
      .filter(Boolean)

    return normalized.length > 0 ? Array.from(new Set(normalized)) : null
  }

  if (Array.isArray(value)) {
    return sanitize(value)
  }

  if (typeof value === 'string') {
    const trimmed = value.trim()

    if (!trimmed) {
      return null
    }

    try {
      const parsed = JSON.parse(trimmed)

      if (Array.isArray(parsed)) {
        return sanitize(parsed)
      }
    } catch (error) {
      // ignore JSON parse errors and fallback to comma-separated values
    }

    return sanitize(trimmed.split(','))
  }

  return null
}

function deriveInternalPlugins() {
  const configuredPlugins = normalizePluginList(param(INTERNAL_PLUGIN_FLAG_PATH))

  if (configuredPlugins) {
    return configuredPlugins
  }

  const tokens = new Set()
  ;[TOOLBAR_1, TOOLBAR_2, INSERT_BUTTON_ITEMS].forEach(bar => {
    if (!bar) {
      return
    }

    bar.split(/\s+/)
      .map(token => token.trim())
      .filter(token => token && token !== '|')
      .forEach(token => tokens.add(token))
  })

  const plugins = new Set(CONFIG_DEPENDENT_PLUGINS)

  tokens.forEach(token => {
    const mapped = TOOLBAR_PLUGIN_MAP[token]

    if (mapped) {
      mapped.forEach(name => plugins.add(name))
    }

    if (defaultPluginNames.indexOf(token) !== -1) {
      plugins.add(token)
    }
  })

  return Array.from(plugins)
}

function loadTinymceConfig() {
  if (!configPromise) {
    const currentLocale = locale()
    const internalPlugins = deriveInternalPlugins()

    configPromise = Promise.all([
      loadTinymcePlugins(internalPlugins),
      getExternalPlugins(),
      loadTinymceTheme(),
      loadTinymceLanguage(currentLocale)
    ]).then(([availablePlugins, extPlugins]) => {
      const plugins = availablePlugins.concat(extPlugins)
      const extButtons = extPlugins.length > 0 ? ` | ${extPlugins.join(' ')}` : ''

      return {
        //TODO: this is for retro comp purpose
        setup: (editor) => {
          editor.on('change', () => editor.save())
          editor.on('FullscreenStateChanged', (evt) => evt.state ?
            editor.getContainer().parentNode.classList.add('editor-fullscreen') :
            editor.getContainer().parentNode.classList.remove('editor-fullscreen')
          )
        },
        allow_script_urls: true,
        language: currentLocale,
        theme: 'modern',
        skin: false, // we provide it through theme system
        content_css: [
          // reuse current platform theme for content
          theme('bootstrap')
        ],
        menubar: true,
        statusbar: true,
        branding: false,
        resize: true,

        // enabled plugins
        plugins,

        // plugin : autoresize
        autoresize_min_height: 160,
        autoresize_max_height: 500,

        // allow to fetch tinymce plugins
        baseURL: asset('packages/tinymce'),
        relative_urls: false,

        // plugin : paste
        paste_data_images: true,
        paste_preprocess: (plugin, args) => {
          if (param('openGraph.enabled') && args.content) {
            const link = args.content.trim()
            if (link && urlValidator(link)) {
              args.target.setProgressState(true)

              const linkPlaceholder = `<span id="url-content-${makeId()}">${link}</span>`
              args.content = linkPlaceholder

              fetch(
                url(['claroline_can_generate_content']), {
                  credentials: 'include',
                  method: 'POST',
                  body: JSON.stringify({url: link})
                })
                .then(response => {
                  if (response.ok) {
                    return response.text()
                  }
                })
                .then(responseText => {
                  let content = args.target.getContent()
                  if (responseText) {
                    content = content.replace(linkPlaceholder, responseText)
                  } else {
                    content = content.replace(linkPlaceholder, link)
                  }

                  // hide loader
                  args.target.setProgressState(false)

                  // replace content in editor
                  args.target.setContent(content)
                })
                .catch((error) => {
                  // creates log error
                  invariant(false, error.message)

                  // displays generic error in ui
                  args.target.notificationManager.open({type: 'error', text: trans('error_occurred')})

                  // hide loader
                  args.target.setProgressState(false)
                })
            }
          }
        },

        // plugin : insertdatetime
        insertdatetime_formats: [ // todo configure
          '%H:%M:%S',
          '%Y-%m-%d',
          '%I:%M:%S %p',
          '%D'
        ],

        // plugin : codemirror
        codemirror: {
          // todo : find a way to reuse our instance of codemirror
          path: asset('packages/tinymce-codemirror/plugins/codemirror/codemirror-4.8')
        },

        extended_valid_elements: 'user[id], a[data-toggle|data-parent], span[*]',
        remove_script_host: false,
        browser_spellcheck: true,

        // toolbars & buttons
        insert_button_items: INSERT_BUTTON_ITEMS,
        toolbar1: TOOLBAR_1, // TODO : find a way to restore fullscreen mode
        toolbar2: TOOLBAR_2 + extButtons
      }
    })
  }

  return configPromise
}

export {
  loadTinymceConfig
}
