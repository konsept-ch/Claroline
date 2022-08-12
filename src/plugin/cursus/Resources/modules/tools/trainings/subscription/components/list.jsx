import React, {useState} from 'react'
import {ControlLabel} from 'react-bootstrap'
import {PropTypes as T} from 'prop-types'
import {trans} from '#/main/app/intl/translation'
import {LINK_BUTTON} from '#/main/app/buttons'
import {ToolPage} from '#/main/core/tool/containers/page'
import {SubscriptionList as SubscriptionDataList} from '#/plugin/cursus/subscription/components/list'
import {selectors} from '#/plugin/cursus/tools/trainings/quota/store/selectors'
import {Select} from '#/main/app/input/components/select'
import moment from 'moment'

const SubscriptionList = (props) => {
  const [year, setYear] = useState(moment().year())

  return <ToolPage
    path={[{
      type: LINK_BUTTON,
      label: trans('subscriptions', {}, 'cursus'),
      target: props.path
    }]}
    subtitle={trans('subscriptions', {}, 'cursus')}
  >
    <ControlLabel>{trans('exercice', {}, 'cursus')}</ControlLabel>
    <Select
      id="year"
      choices={Array.from({length:31}, (_, i) => 2020 + i).reduce((acc, year) => {
        return {
          ...acc,
          [year]: `${year}`
        }}, {})
      } 
      onChange={value => {
        setYear(value)
      }}
      value={year}
    />
    <SubscriptionDataList
      name={selectors.LIST_NAME}
      path={props.path}
    />
  </ToolPage>
}

SubscriptionList.propTypes = {
  path: T.string.isRequired
}

export {
  SubscriptionList
}
