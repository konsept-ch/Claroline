import React, {Fragment, useEffect} from 'react'
import {PropTypes as T} from 'prop-types'
import isEmpty from 'lodash/isEmpty'
import get from 'lodash/get'

import {LINK_BUTTON, DOWNLOAD_BUTTON} from '#/main/app/buttons'
import {trans} from '#/main/app/intl/translation'
import {ContentCounter} from '#/main/app/content/components/counter'
import {ContentLoader} from '#/main/app/content/components/loader'
import {PageFull} from '#/main/app/page/components/full'
import {getToolBreadcrumb, showToolBreadcrumb} from '#/main/core/tool/utils'
import {YearSelector} from '#/plugin/cursus/subscription/components/year-selector'
import {getQuotaByYear} from '#/plugin/cursus/utils'

import {SubscriptionAll} from '#/plugin/cursus/tools/trainings/subscription/components/all'
import {
  Quota as QuotaTypes,
  Statistics as StatisticsTypes
} from '#/plugin/cursus/prop-types'
import {selectors} from '#/plugin/cursus/tools/trainings/subscription/store/selectors'

const SubscriptionPage = (props) => {
  if (isEmpty(props.quota)) {
    return (
      <ContentLoader
        size="lg"
        description={trans('training_loading', {}, 'cursus')}
      />
    )
  }

  useEffect(() => {
    if (!isEmpty(props.quota)) {
      props.getStatistics(props.quota.id, props.year)
    }
  }, [props.quota])

  return (
    <PageFull
      showBreadcrumb={showToolBreadcrumb(props.currentContext.type, props.currentContext.data)}
      path={[].concat(getToolBreadcrumb('trainings', props.currentContext.type, props.currentContext.data), [{
        type: LINK_BUTTON,
        label: trans('subscriptions', {}, 'cursus'),
        target: props.path
      }, {
        type: LINK_BUTTON,
        label: props.quota.id,
        target: props.path
      }])}
      title={props.quota.organization.name}
      toolbar="fullscreen more"
      actions={[
        {
          name: 'export-csv-with-filter',
          type: DOWNLOAD_BUTTON,
          icon: 'fa fa-fw fa-download',
          label: trans('export_with_filter', {}, 'actions'),
          file: {
            url: ['apiv2_cursus_quota_export', {id: props.quota.id, year: props.year, filters: props.filters}]
          },
          group: trans('transfer')
        },
        {
          name: 'export-csv',
          type: DOWNLOAD_BUTTON,
          icon: 'fa fa-fw fa-download',
          label: trans('export_all', {}, 'actions'),
          file: {
            url: ['apiv2_cursus_quota_export', {id: props.quota.id, year: props.year}]
          },
          group: trans('transfer')
        }
      ]}
    >
      <Fragment>
        <div className="row">
          <div className="col-md-12">
            <YearSelector value={props.year} onChange={(value) => props.updateYear(props.quota.id, value)} />
          </div>
        </div>
        <div className="row">
          <ContentCounter
            icon="fa fa-chalkboard-teacher"
            label={trans('subscription_total', {}, 'cursus')}
            color="rgb(51, 87, 104)"
            value={props.statistics.total}
          />
          <ContentCounter
            icon="fa fa-pause"
            label={trans('subscription_pending', {}, 'cursus')}
            color="rgb(119, 119, 119)"
            value={props.statistics.pending}
          />
          <ContentCounter
            icon="fa fa-times"
            label={trans('subscription_refused', {}, 'cursus')}
            color="rgb(191, 4, 4)"
            value={props.statistics.refused}
          />
          <ContentCounter
            icon="fa fa-check"
            label={trans('subscription_validated', {}, 'cursus')}
            color="rgb(237, 158, 47)"
            value={props.statistics.validated}
          />
          {getQuotaByYear(props).enabled && props.statistics.calculated != undefined &&
            <ContentCounter
              icon="fa fa-check-double"
              label={trans('subscription_managed', {}, 'cursus')}
              color="rgb(79, 115, 2)"
              value={props.statistics.managed}
            />
          }
          {getQuotaByYear(props).enabled && props.statistics.calculated != undefined &&
            <ContentCounter
              icon="fa fa-chart-pie"
              label={trans('subscription_quota', {}, 'cursus')}
              color="rgb(51, 122, 183)"
              value={`${props.statistics.calculated.toFixed(2)} / ${get(getQuotaByYear(props), 'quota')}`}
            />
          }
        </div>

        <div className="row">
          <div className="col-md-12">
            <SubscriptionAll
              name={selectors.LIST_NAME}
              url={['apiv2_cursus_quota_list_subscriptions', {id: props.quota.id, year: props.year}]}
              path={props.path}
              setSubscriptionStatus={props.setSubscriptionStatus}
              statistics={props.statistics}
              quota={props.quota}
              isAdmin={props.isAdmin}
              year={props.year}
            />
          </div>
        </div>
      </Fragment>
    </PageFull>
  )
}

SubscriptionPage.propTypes = {
  path: T.string.isRequired,
  currentContext: T.shape({
    type: T.oneOf(['desktop']),
    data: T.object
  }).isRequired,
  primaryAction: T.string,
  actions: T.array,
  quota: T.shape(
    QuotaTypes.propTypes
  ),
  filters: T.shape({}).isRequired, // Add PropType for filters
  statistics: T.shape(
    StatisticsTypes.propTypes
  ).isRequired,
  getStatistics: T.func.isRequired,
  setSubscriptionStatus: T.func.isRequired,
  isAdmin: T.bool.isRequired,
  year: T.number.isRequired,
  updateYear: T.func.isRequired
}

export {
  SubscriptionPage
}
