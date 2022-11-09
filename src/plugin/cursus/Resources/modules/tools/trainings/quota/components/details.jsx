import React from 'react'
import {PropTypes as T} from 'prop-types'

import {trans} from '#/main/app/intl/translation'
import {LINK_BUTTON} from '#/main/app/buttons'

import {Quota as QuotaTypes} from '#/plugin/cursus/prop-types'
import {QuotaPage} from '#/plugin/cursus/quota/components/page'
import {QuotaForm} from '#/plugin/cursus/quota/containers/form'

import {selectors} from '#/plugin/cursus/tools/trainings/quota/store'

const QuotaDetails = (props) =>
  <QuotaPage
    basePath={props.path}
    path={props.quota ? [
      {
        type: LINK_BUTTON,
        label: trans('quotas', {}, 'cursus'),
        target: props.path
      }, {
        label: props.quota.id
      }
    ] : undefined}
    currentContext={props.currentContext}
    quota={props.quota}
  >
    {props.quota &&
      <QuotaForm
        path={props.path}
        name={selectors.FORM_NAME}
      />
    }
  </QuotaPage>

QuotaDetails.propTypes = {
  path: T.string.isRequired,
  currentContext: T.shape({
    type: T.oneOf(['desktop']),
    data: T.object
  }).isRequired,
  quota: T.shape(
    QuotaTypes.propTypes
  )
}

export {
  QuotaDetails
}