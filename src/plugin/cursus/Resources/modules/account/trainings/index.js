import {trans} from '#/main/app/intl/translation'

import {AccountTrainingsMain} from '#/plugin/cursus/account/trainings/containers/main'

export default {
  name: 'trainings',
  icon: 'fa fa-fw fa-graduation-cap',
  label: trans('my_courses', {}, 'cursus'),
  component: AccountTrainingsMain
}
