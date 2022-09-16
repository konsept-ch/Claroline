import {trans} from '#/main/app/intl/translation'

import {QuotaInput} from '#/plugin/cursus/data/types/quota/components/input'

const dataType = {
  name: 'quota',
  meta: {
    creatable: false,
    icon: 'fa fa-fw fa fa-calendar-week',
    label: trans('quota', {}, 'data'),
    description: trans('quota_desc', {}, 'data')
  },
  render: (raw) => raw ? raw.name : null,
  components: {
    input: QuotaInput
  }
}

export {
  dataType
}
