import React from 'react'
import {trans} from '#/main/app/intl/translation'
import {Select} from '#/main/app/input/components/select'
import {PropTypes as T} from 'prop-types'

const YearSelector = (props) =>
  <div style={{paddingBottom: '1rem'}}>
    <h4>
      <strong>{trans('exercice', {}, 'cursus')} {props.value}</strong>
    </h4>
    <Select
      id="year"
      choices={Array.from({length:31}, (_, i) => 2020 + i).reduce((acc, year) => {
        return {
          ...acc,
          [year]: year
        }}, {})
      } 
      onChange={value => props.onChange(value)}
      value={props.value}
    />
  </div>

YearSelector.propTypes = {
  value: T.number.isRequired,
  onChange: T.func.isRequired
}

export {
  YearSelector
}