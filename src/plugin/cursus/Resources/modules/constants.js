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
const SUBSCRIPTION_STATUS_CANCELLED = 4

const SUBSCRIPTION_STRINGS = {
  [SUBSCRIPTION_STATUS_PENDING]: 'subscription_pending',
  [SUBSCRIPTION_STATUS_REFUSED]: 'subscription_refused',
  [SUBSCRIPTION_STATUS_VALIDATED]: 'subscription_validated',
  [SUBSCRIPTION_STATUS_MANAGED]: 'subscription_managed',
  [SUBSCRIPTION_STATUS_CANCELLED]: 'subscription_cancelled'
}

const SUBSCRIPTION_STATUSES = {
  [SUBSCRIPTION_STATUS_PENDING]: trans('subscription_pending', {}, 'cursus'),
  [SUBSCRIPTION_STATUS_REFUSED]: trans('subscription_refused', {}, 'cursus'),
  [SUBSCRIPTION_STATUS_VALIDATED]: trans('subscription_validated', {}, 'cursus'),
  [SUBSCRIPTION_STATUS_MANAGED]: trans('subscription_managed', {}, 'cursus'),
  [SUBSCRIPTION_STATUS_CANCELLED]: trans('subscription_cancelled', {}, 'cursus')
}

const SUBSCRIPTION_STATUS_COLORS = {
  [SUBSCRIPTION_STATUS_PENDING]: 'default',
  [SUBSCRIPTION_STATUS_REFUSED]: 'danger',
  [SUBSCRIPTION_STATUS_VALIDATED]: 'warning',
  [SUBSCRIPTION_STATUS_MANAGED]: 'success',
  [SUBSCRIPTION_STATUS_CANCELLED]: 'info'
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
  SUBSCRIPTION_STATUS_PENDING
}
