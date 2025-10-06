import {now} from '#/main/app/intl/date'

import {selectors as securitySelectors} from '#/main/app/security/store/selectors'
import {selectors as configSelectors} from '#/main/app/config/store/selectors'

const disabled = (state) => {
  const firstDate = configSelectors.param(state, 'restrictions.dates[0]')
  const lastDate  = configSelectors.param(state, 'restrictions.dates[1]')

  const started = !firstDate || firstDate < now(false)
  const ended   = !!(lastDate && lastDate < now(false))

  const isDisabled = !!configSelectors.param(state, 'restrictions.disabled')

  // Always return a boolean
  return isDisabled || !started || ended
}

const maintenance = state => state.maintenance.enabled
const maintenanceMessage = state => state.maintenance.message

const unavailable = (state) => !!(disabled(state) || (!securitySelectors.isAuthenticated(state) && maintenance(state)))

const sidebar = state => state.sidebar.name

export const selectors = {
  unavailable,
  disabled,
  maintenance,
  maintenanceMessage,
  sidebar
}
