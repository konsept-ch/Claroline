import {trans} from '#/main/app/intl/translation'

const DEFAULT_ORDER = 1

const REGISTRATION_AUTO = 0
const REGISTRATION_MANUAL = 1
const REGISTRATION_PUBLIC = 2

const REGISTRATION_TYPES = {
  [REGISTRATION_AUTO]: trans('event_registration_automatic', {}, 'cursus'),
  [REGISTRATION_MANUAL]: trans('event_registration_manual', {}, 'cursus'),
  [REGISTRATION_PUBLIC]: trans('event_registration_public', {}, 'cursus')
}

const PRESENCE_STATUS_UNKNOWN = 'unknown'
const PRESENCE_STATUS_PRESENT = 'present'
const PRESENCE_STATUS_ABSENT_JUSTIFIED = 'absent_justified'
const PRESENCE_STATUS_ABSENT_UNJUSTIFIED = 'absent_unjustified'

const PRESENCE_STATUSES = {
  [PRESENCE_STATUS_UNKNOWN]: trans('presence_unknown', {}, 'cursus'),
  [PRESENCE_STATUS_PRESENT]: trans('presence_present', {}, 'cursus'),
  [PRESENCE_STATUS_ABSENT_JUSTIFIED]: trans('presence_absent_justified', {}, 'cursus'),
  [PRESENCE_STATUS_ABSENT_UNJUSTIFIED]: trans('presence_absent_unjustified', {}, 'cursus')
}

const PRESENCE_STATUS_COLORS = {
  [PRESENCE_STATUS_UNKNOWN]: 'default',
  [PRESENCE_STATUS_PRESENT]: 'success',
  [PRESENCE_STATUS_ABSENT_JUSTIFIED]: 'warning',
  [PRESENCE_STATUS_ABSENT_UNJUSTIFIED]: 'danger'
}

const SUBSCRIPTION_STATUS_PENDING = 0
const SUBSCRIPTION_STATUS_REFUSED = 1
const SUBSCRIPTION_STATUS_VALIDATED = 2
const SUBSCRIPTION_STATUS_MANAGED = 3

const SUBSCRIPTION_STRINGS = {
  [SUBSCRIPTION_STATUS_PENDING]: 'subscription_pending',
  [SUBSCRIPTION_STATUS_REFUSED]: 'subscription_refused',
  [SUBSCRIPTION_STATUS_VALIDATED]: 'subscription_validated',
  [SUBSCRIPTION_STATUS_MANAGED]: 'subscription_managed'
}

const SUBSCRIPTION_STATUSES = {
  [SUBSCRIPTION_STATUS_PENDING]: trans('subscription_pending', {}, 'cursus'),
  [SUBSCRIPTION_STATUS_REFUSED]: trans('subscription_refused', {}, 'cursus'),
  [SUBSCRIPTION_STATUS_VALIDATED]: trans('subscription_validated', {}, 'cursus'),
  [SUBSCRIPTION_STATUS_MANAGED]: trans('subscription_managed', {}, 'cursus')
}

const SUBSCRIPTION_STATUS_COLORS = {
  [SUBSCRIPTION_STATUS_PENDING]: 'default',
  [SUBSCRIPTION_STATUS_REFUSED]: 'danger',
  [SUBSCRIPTION_STATUS_VALIDATED]: 'warning',
  [SUBSCRIPTION_STATUS_MANAGED]: 'success'
}


const REGISTRATION_STATE_PENDING = 0
const REGISTRATION_STATE_VALIDATED = 1
const REGISTRATION_STATE_REFUSED = 2
const REGISTRATION_STATE_CANCELLED = 3
const REGISTRATION_STATE_PARTICIPATED = 4

const REGISTRATION_STATES = {
  [REGISTRATION_STATE_PENDING]: trans('registration_pending', {}, 'cursus'),
  [REGISTRATION_STATE_VALIDATED]: trans('registration_validated', {}, 'cursus'),
  [REGISTRATION_STATE_REFUSED]: trans('registration_refused', {}, 'cursus'),
  [REGISTRATION_STATE_CANCELLED]: trans('registration_cancelled', {}, 'cursus'),
  [REGISTRATION_STATE_PARTICIPATED]: trans('registration_participated', {}, 'cursus')
}

const REGISTRATION_STATE_COLORS = {
  [REGISTRATION_STATE_PENDING]: 'default',
  [REGISTRATION_STATE_VALIDATED]: 'info',
  [REGISTRATION_STATE_REFUSED]: 'danger',
  [REGISTRATION_STATE_CANCELLED]: 'warning',
  [REGISTRATION_STATE_PARTICIPATED]: 'success'
}

const LEARNER_TYPE = 'learner'
const TEACHER_TYPE = 'tutor'

export const constants = {
  DEFAULT_ORDER,
  REGISTRATION_AUTO,
  REGISTRATION_MANUAL,
  REGISTRATION_PUBLIC,
  REGISTRATION_TYPES,
  PRESENCE_STATUSES,
  PRESENCE_STATUS_COLORS,
  SUBSCRIPTION_STRINGS,
  SUBSCRIPTION_STATUSES,
  SUBSCRIPTION_STATUS_COLORS,
  LEARNER_TYPE,
  TEACHER_TYPE,
  SUBSCRIPTION_STATUS_PENDING,
  REGISTRATION_STATES,
  REGISTRATION_STATE_COLORS
}
