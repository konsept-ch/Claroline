import React, {Fragment, useState, useEffect} from 'react'

import {FormControl, ControlLabel, Checkbox} from 'react-bootstrap'

import {ContentSection, ContentSections} from '#/main/app/content/components/sections'

import {Button} from '#/main/app/action/components/button'
import {CALLBACK_BUTTON} from '#/main/app/buttons'

import {trans} from '#/main/app/intl/translation'
import {PropTypes as T, implementPropTypes} from '#/main/app/prop-types'
import {DataInput as DataInputTypes} from '#/main/app/data/types/prop-types'

const QuotaInput = props => {
  const [choices, setChoices] = useState(Array.from({length:31}, (_, i) => 2020 + i).reduce((acc, year) => {
    return {
      ...acc,
      [year]: !props.value.years[year]
    }
  }, {}))
  const [choice, setChoice] = useState()

  useEffect(() => setChoice(Object.entries(choices).find(entry => entry[1] && entry[0] != choice)[0]), [choices])

  return (
    <Fragment>
      <ContentSections>
        <ContentSection title={trans('default')}>
          <ControlLabel>{trans('enabled')}</ControlLabel>
          <Checkbox name="quota_default_enabled" checked={props.value.default.enabled} onChange={e => props.onChange({
            default: {
              enabled: e.target.checked,
              quota: props.value.default.quota
            },
            years: props.value.years
          })} />
          <ControlLabel>{trans('quota', {}, 'cursus')}</ControlLabel>
          <FormControl name="quota_default_quota" type="number" value={props.value.default.quota} onChange={e => props.onChange({
            default: {
              enabled: props.value.default.enabled,
              quota: e.target.value
            },
            years: props.value.years
          })} />
        </ContentSection>

        {Object.entries(props.value.years).sort((a, b) => a[0] - b[0]).map(([year, {enabled, quota}]) =>
          <ContentSection key={year} title={year} actions={[
            {
              name: 'remove-year',
              type: CALLBACK_BUTTON,
              icon: 'fa fa-fw fa-trash',
              label: trans('remove_year'),
              callback: () => {
                // eslint-disable-next-line no-unused-vars
                const {[year]: _, ...restYears} = props.value.years
                props.onChange({
                  default: props.value.default,
                  years: restYears
                })
                setChoices({
                  ...choices,
                  [year]: true
                })
              }
            }
          ]}>
            <ControlLabel>{trans('enabled')}</ControlLabel>
            <Checkbox name={`quota_${year}_enabled`} checked={enabled} onChange={e => props.onChange({
              default: props.value.default,
              years: {
                ...props.value.years,
                [year]: {
                  enabled: e.target.checked,
                  quota
                }
              }
            })} />
            <ControlLabel>{trans('quota', {}, 'cursus')}</ControlLabel>
            <FormControl name={`quota_${year}_quota`} type="number" value={quota} onChange={e => props.onChange({
              default: props.value.default,
              years: {
                ...props.value.years,
                [year]: {
                  enabled,
                  quota: e.target.value
                }
              }
            })} />
          </ContentSection>
        )}
      </ContentSections>

      <FormControl componentClass="select" value={choice} onChange={e => setChoice(e.target.value)}>
        {Object.entries(choices).filter(c => c[1]).map(([year]) =>
          <option key={year} value={year}>{year}</option>
        )}
      </FormControl>
      <br />
      <Button
        type={CALLBACK_BUTTON}
        className="btn btn-block"
        icon="fa fa-fw fa-plus"
        label={trans('add_year', {}, 'cursus')}
        size={props.size}
        callback={() => {
          props.onChange({
            default: props.value.default,
            years: {
              ...props.value.years,
              [choice]: props.value.default
            }
          })
          setChoices({
            ...choices,
            [choice]: false
          })
        }}
      />
    </Fragment>
  )
}

implementPropTypes(QuotaInput, DataInputTypes, {
  value: T.object.isRequired
}, {
  value: {
    default: {
      enabled: false,
      quota: 0
    },
    years: {
    }
  }
})

export {
  QuotaInput
}
