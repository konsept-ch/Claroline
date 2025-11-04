const fs = require('fs')
const path = require('path')

const ASSET_FILTER_FILENAME = '.claroline-assets.json'
const ADMIN_OVERRIDE_ENV = 'CLAROLINE_INCLUDE_ADMIN_ASSETS'

/**
 * Collects information about currently installed
 * claroline packages. Each package will be represented
 * as an object literal with the following attributes:
 *
 *  - name:      name of the package declared in its composer.json file
 *  - path:      path of the package source directory
 *  - assets:    package assets config declared its assets.json file, if any
 */
function collect(rootDir) {
  const stats = fs.statSync(rootDir)

  if (!stats.isDirectory()) {
    throw new Error(`${rootDir} is not a directory`)
  }

  const preferences = loadAssetPreferences(rootDir)

  return applyAssetFilters([
    {
      name: 'claroline-distribution',
      composerName: 'claroline-distribution',
      path: `${rootDir}/src`,
      meta: true,
      adminOnly: false,
      assets: getMetaEntries(`${rootDir}/src`)
    }
  ].concat(normalizeNames(getDefinitions(rootDir))), preferences)
}

function getDefinitions(rootDir) {
  const file = `${rootDir}/vendor/composer/installed.json`
  let data

  try {
    data = fs.readFileSync(file, 'utf8')
  } catch (err) {
    throw new Error('Cannot found package info (composer/installed.json)')
  }

  let packages = JSON.parse(data)

  // composer 2
  if (typeof packages['packages'] !== 'undefined') {
    packages = packages['packages'];
  }

  if (!(packages instanceof Array) || packages.length < 1) {
    throw new Error('Cannot find packages in composer/installed.json')
  }

  const filteredPackages = packages.filter(
    def => def.type === 'claroline-core' || def.type === 'claroline-plugin'
  )

  return filteredPackages.map(extractPackageInfo(rootDir))
}

function extractPackageInfo(rootDir) {
  return def => {
    const targetDir = def['target-dir'] ? `/${def['target-dir']}` : ''
    const packagePath = `${rootDir}/vendor/${def.name}${targetDir}`
    const newDef = {
      name: def.name,
      composerName: def.name,
      path: packagePath,
      assets: false,
      meta: false,
      adminOnly: false
    }
    let data

    if (isMetaPackage(packagePath)) {
      newDef.assets = getMetaEntries(packagePath)
      newDef.meta = true
    } else {
      try {
        data = fs.readFileSync(`${packagePath}/assets.json`, 'utf8')
        newDef.assets = JSON.parse(data)
        newDef.adminOnly = detectAdminOnly(newDef.assets)
      } catch (err) {}
    }

    return newDef
  }
}

function getMetaEntries (targetDir) {
  let data
  const assets = {}

  getMetaBundles(targetDir).forEach(bundle => {
    try {
      data = JSON.parse(fs.readFileSync(`${bundle}/assets.json`, 'utf8'))

      Object.keys(data).forEach(assetType => {
        Object.keys(data[assetType].entry).forEach(entry => {
          const parts = bundle.split('/')
          const bundleName = parts.pop()
          const lastDir = parts[parts.length - 1]

          if (!assets[assetType]) {
            assets[assetType] = { entry: {} }
          }

          assets[assetType].entry[`${bundleName}-${entry}`] = {
            name: data[assetType].entry[entry],
            prefix: bundle,
            dir: lastDir,
            bundle: bundleName
          }
        })
      })
    } catch(err) {}
  })

  return assets
}

function isMetaPackage(rootDir) {
  return fs.existsSync(rootDir + '/main') || fs.existsSync(rootDir + '/plugin') || fs.existsSync(rootDir + '/integration')
}

function getMetaBundles(targetDir) {
  let bundles = []
  const src = ['main', 'plugin', 'integration']

  src.filter(dir => fs.existsSync(targetDir + '/' + dir)).forEach(el => {
    const dir = targetDir + '/' + el
    bundles = bundles.concat(fs.readdirSync(dir).map(el => {
      return dir + '/' + el}))
  })

  return bundles
}

/**
 * Removes the "bundle" portion of package names and replaces
 * slashes by hyphens. Example:
 *
 * "foo/bar-bundle" -> "foo-bar"
 */
function normalizeNames(packages) {
  return packages.map(def => {
    def.name = normalizeName(def.name)
    return def
  })
}

/**
 *
 * @param name
 * @returns {*}
 */
function normalizeName(name) {
  const parts = name.split(/\/|\-/)

  if (parts[parts.length - 1] === 'bundle') {
    parts.pop()
  }

  name = parts.join('-')

  return name
}

function loadAssetPreferences(rootDir) {
  const configPath = path.join(rootDir, ASSET_FILTER_FILENAME)
  let config = {}

  try {
    const raw = fs.readFileSync(configPath, 'utf8')
    config = JSON.parse(raw)
  } catch (err) {
    if (err.code !== 'ENOENT') {
      throw new Error(`Cannot parse ${ASSET_FILTER_FILENAME}: ${err.message}`)
    }
  }

  const enabled = parseNameList(config.enabled)
  const disabled = parseNameList(config.disabled) || new Set()
  const adminOverrides = parseAdminOverrides(config.adminOnly)

  return {
    enabled,
    disabled,
    adminOverrides,
    includeAdmin: resolveIncludeAdmin(config)
  }
}

function parseNameList(list) {
  if (!Array.isArray(list)) {
    return null
  }

  const names = list
    .filter(value => typeof value === 'string')
    .map(value => value.trim())
    .filter(Boolean)

  return names.length > 0 ? new Set(names) : null
}

function parseAdminOverrides(overrides) {
  const map = new Map()

  if (!overrides || typeof overrides !== 'object') {
    return map
  }

  Object.keys(overrides).forEach(name => {
    if (typeof overrides[name] === 'boolean') {
      const trimmed = name.trim()

      if (trimmed) {
        map.set(trimmed, overrides[name])
      }
    }
  })

  return map
}

function resolveIncludeAdmin(config) {
  if (typeof process.env[ADMIN_OVERRIDE_ENV] !== 'undefined') {
    return parseTruthy(process.env[ADMIN_OVERRIDE_ENV])
  }

  if (typeof config.includeAdminBundles === 'boolean') {
    return config.includeAdminBundles
  }

  if (typeof config.includeAdminAssets === 'boolean') {
    return config.includeAdminAssets
  }

  return process.env.NODE_ENV !== 'production'
}

function applyAssetFilters(packages, preferences) {
  if (!preferences) {
    return packages
  }

  const enabled = preferences.enabled && preferences.enabled.size ? preferences.enabled : null
  const disabled = preferences.disabled || new Set()
  const adminOverrides = preferences.adminOverrides || new Map()
  const includeAdmin = !!preferences.includeAdmin

  return packages.filter(def => {
    if (enabled && !packageInList(def, enabled)) {
      // Always include meta packages unless explicitly disabled.
      if (!def.meta) {
        return false
      }
    }

    if (disabled.size && packageInList(def, disabled)) {
      return false
    }

    const override = getOverride(def, adminOverrides)
    const adminOnly = typeof override === 'boolean' ? override : !!def.adminOnly
    const explicitlyEnabled = enabled ? packageInList(def, enabled) : false

    if (adminOnly && !includeAdmin && !explicitlyEnabled) {
      return false
    }

    def.adminOnly = adminOnly

    return true
  })
}

function packageInList(def, set) {
  return set.has(def.name) || (def.composerName && set.has(def.composerName))
}

function getOverride(def, overrides) {
  if (!overrides.size) {
    return undefined
  }

  if (overrides.has(def.name)) {
    return overrides.get(def.name)
  }

  if (def.composerName && overrides.has(def.composerName)) {
    return overrides.get(def.composerName)
  }

  return undefined
}

function detectAdminOnly(assets) {
  if (!assets || typeof assets !== 'object') {
    return false
  }

  if (assets.adminOnly === true) {
    return true
  }

  if (assets.metadata && assets.metadata.adminOnly === true) {
    return true
  }

  if (assets.metadata && assets.metadata.visibility && assets.metadata.visibility.toLowerCase() === 'admin') {
    return true
  }

  if (assets.claroline && assets.claroline.adminOnly === true) {
    return true
  }

  return false
}

function parseTruthy(value) {
  if (typeof value !== 'string') {
    return !!value
  }

  const normalized = value.trim().toLowerCase()

  return normalized === '1' || normalized === 'true' || normalized === 'yes' || normalized === 'on'
}

module.exports = {
  collect,
  isMetaPackage,
  getMetaBundles,
  normalizeNames,
  normalizeName
}
