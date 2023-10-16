import React, {Component} from 'react'
import classes from 'classnames'
import get from 'lodash/get'
import isArray from 'lodash/isArray'
import moment from 'moment'

import {getApiFormat} from '#/main/app/intl/date'
import {trans} from '#/main/app/intl/translation'

import {PropTypes as T, implementPropTypes} from '#/main/app/prop-types'
import {DataInput as DataInputTypes} from '#/main/app/data/types/prop-types'
import {DataError} from '#/main/app/data/components/error'
import {Select} from '#/main/app/input/components/select'

import {DateInput} from '#/main/app/data/types/date/components/input'

class DateEventInput extends Component {
  constructor(props) {
    super(props)

    this.state = {
      date: moment(),
      period: 'fd'
    }

    if (props.value) {
      const d = moment(props.value[0])
      const s = d.hours()
      const e = moment(props.value[1]).hours()
      this.state.date = d.hours(0).minutes(0).seconds(0).format(getApiFormat())
      this.state.period = s > 0 && e <= 14 ? 'am' : s > 10 && e < 23 ? 'pm' : 'fd'
    }

    this.setDate = this.setDate.bind(this)
    this.setPeriod = this.setPeriod.bind(this)
  }

  getUtcMoment(d, h, m, s) {
    return d.hours(h).minutes(m).seconds(s).utc().format(getApiFormat())
  }

  invokeChange(newState) {
    const { date, period } = newState
    const range = period == 'am' ? [8, 30, 12, 0] : (period == 'pm' ? [13, 30, 17, 0] : [8, 30, 17, 0])

    this.setState(newState)
    this.props.onChange([this.getUtcMoment(moment(date), range[0], range[1], 0), this.getUtcMoment(moment(date), range[2], range[3], 0)])
  }

  setDate(date) {
    this.invokeChange({
      date,
      period: this.state.period
    })
  }

  setPeriod(period) {
    this.invokeChange({
      date: this.state.date,
      period
    })
  }

  render() {
    return (
      <div className={classes('row', this.props.className)}>
        <div className={classes('form-group col-md-6 col-xs-12', {
          'has-error'  : isArray(this.props.error) && get(this.props, 'error[0]') && this.props.validating,
          'has-warning': isArray(this.props.error) && get(this.props, 'error[0]') && !this.props.validating
        })}>
          <DateInput
            id={`${this.props.id}-date`}
            calendarIcon="fa fa-fw fa-calendar-check-o"
            value={this.state.date}
            disabled={this.props.disabled}
            onChange={this.setDate}
            minDate={this.props.minDate}
            maxDate={this.props.maxDate}
          />

          {isArray(this.props.error) && get(this.props, 'error[0]') &&
            <DataError error={get(this.props, 'error[0]')} warnOnly={!this.props.validating} />
          }
        </div>

        <div className={classes('form-group col-md-6 col-xs-12')}>
          <Select
            choices={{
              'am': trans('period_am', {}, 'cursus'),
              'pm': trans('period_pm', {}, 'cursus'),
              'fd': trans('period_fd', {}, 'cursus')
            }}
            id={`${this.props.id}-period`}
            value={this.state.period}
            onChange={this.setPeriod}
          />
        </div>
      </div>
    )
  }
}

implementPropTypes(DateEventInput, DataInputTypes, {
  // more precise value type
  value: T.arrayOf(T.string),

  // date configuration
  minDate: T.string,
  maxDate: T.string,
}, {
  value: null
})

export {
  DateEventInput
}
