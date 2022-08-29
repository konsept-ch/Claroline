import React from 'react'
import {ControlLabel} from 'react-bootstrap'
import {PropTypes as T} from 'prop-types'
import {trans} from '#/main/app/intl/translation'
import {LINK_BUTTON} from '#/main/app/buttons'
import {ToolPage} from '#/main/core/tool/containers/page'
import {SubscriptionList as SubscriptionDataList} from '#/plugin/cursus/subscription/components/list'
import {selectors} from '#/plugin/cursus/tools/trainings/quota/store/selectors'
import {Select} from '#/main/app/input/components/select'

const SubscriptionList = (props) =>
  <ToolPage
    path={[{
      type: LINK_BUTTON,
      label: trans('subscriptions', {}, 'cursus'),
      target: props.path
    }]}
    subtitle={trans('subscriptions', {}, 'cursus')}
  >
    <h4>
      <strong>{trans('exercice', {}, 'cursus')} {props.year}</strong>
    </h4>
    <Select
      id="year"
      choices={Array.from({length:31}, (_, i) => 2020 + i).reduce((acc, year) => {
        return {
          ...acc,
          [year]: year
        }}, {})
      } 
      onChange={value => {
        props.setYear(value)
      }}
      value={props.year}
    />
    <p>&nbsp;</p>
    <SubscriptionDataList
      name={selectors.LIST_NAME}
      path={props.path}
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
