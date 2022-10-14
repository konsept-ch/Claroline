import {connect} from 'react-redux'

import {selectors as toolSelectors} from '#/main/core/tool/store'
import {selectors as formSelectors} from '#/main/app/content/form/store'
import {selectors} from '#/plugin/cursus/tools/trainings/quota/store'

import {QuotaDetails as QuotaDetailsComponent} from '#/plugin/cursus/tools/trainings/quota/components/details'

const QuotaDetails = connect(
  (state) => ({
    currentContext: toolSelectors.context(state),
    quota: formSelectors.data(formSelectors.form(state, selectors.FORM_NAME))
  })
)(QuotaDetailsComponent)

export {
  QuotaDetails
}
