/* global window */

const DEFAULT_DOMAIN    = 'message'
const PLATFORM_DOMAIN   = 'platform'
const VALIDATION_DOMAIN = 'validators'

import {Translator as BaseTranslator} from './translator'

/**
 * Get the current application translator.
 *
 * @returns {Translator}
 */
function getTranslator() {
  // Try to reuse the instance provided by the BazingaJsTranslationBundle script
  // Some setups expose `window.Translator` as a constructor instead of an instance.
  // In that case, instantiate it defensively to avoid `trans is not a function` at runtime.
  const t = (typeof window !== 'undefined') ? window.Translator : undefined

  if (t) {
    // If it already looks like a usable instance
    if (typeof t.trans === 'function') {
      return t
    }

    // If it's a constructor with a proper prototype, instantiate it once
    if (typeof t === 'function' && t.prototype && typeof t.prototype.trans === 'function') {
      try {
        const instance = new t()
        if (typeof window !== 'undefined') {
          window.Translator = instance
        }
        return instance
      } catch (e) {
        // fall back below
      }
    }
  }

  // Fallback to our local implementation (already populated by lazy JSON loads)
  return BaseTranslator
}

/**
 * Exposes standard Translator `trans` function.
 *
 * @param {string} key
 * @param {object} placeholders
 * @param {string} domain
 *
 * @returns {string}
 */
export function trans(key, placeholders = {}, domain = PLATFORM_DOMAIN) {
  return getTranslator().trans(key, placeholders, domain)
}

/**
 * Exposes standard Translator `transChoice` function.
 *
 * @param {string} key
 * @param {number} count
 * @param {object} placeholders
 * @param {string} domain
 *
 * @returns {string}
 */

export function transChoice(key, count, placeholders = {}, domain = PLATFORM_DOMAIN) {
  return getTranslator().transChoice(key, count, placeholders, domain)
}

/**
 * Shortcut to access `validators` messages.
 *
 * @param {string} message
 * @param {object} placeholders
 *
 * @returns {string}
 */
export function tval(message, placeholders = {}) {
  return trans(message, placeholders, VALIDATION_DOMAIN)
}

// reexport translator object
const Translator = getTranslator()
export {
  DEFAULT_DOMAIN,
  PLATFORM_DOMAIN,
  VALIDATION_DOMAIN,
  Translator
}
