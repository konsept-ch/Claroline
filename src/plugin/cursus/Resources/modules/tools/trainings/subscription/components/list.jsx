import React from 'react'
import {PropTypes as T} from 'prop-types'
import {trans} from '#/main/app/intl/translation'
import {LINK_BUTTON} from '#/main/app/buttons'
import {ToolPage} from '#/main/core/tool/containers/page'
import {SubscriptionList as SubscriptionDataList} from '#/plugin/cursus/subscription/components/list'
import {selectors} from '#/plugin/cursus/tools/trainings/quota/store/selectors'
import {YearSelector} from '#/plugin/cursus/subscription/components/year-selector'

const SubscriptionList = (props) =>
  <ToolPage
    path={[{
      type: LINK_BUTTON,
      label: trans('subscriptions', {}, 'cursus'),
      target: props.path
    }]}
    subtitle={trans('subscriptions', {}, 'cursus')}
  >
    <YearSelector value={props.year} onChange={(value) => props.setYear(value)} />
    <SubscriptionDataList
      name={selectors.LIST_NAME}
      path={props.path}
      url={['apiv2_cursus_quota_list_by_year', {year: props.year}]}
    />
  </ToolPage>

SubscriptionList.propTypes = {
  path: T.string.isRequired,
  year: T.number.isRequired,
  setYear: T.func.isRequired
}

export {
  SubscriptionList
}
