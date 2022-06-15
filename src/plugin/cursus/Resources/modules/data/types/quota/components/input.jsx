import React, {Fragment, useState, useEffect} from 'react'

import {FormControl, ControlLabel, Checkbox} from 'react-bootstrap'

import {ContentSection, ContentSections} from '#/main/app/content/components/sections'

import {Button} from '#/main/app/action/components/button'
import {CALLBACK_BUTTON} from '#/main/app/buttons'

import {trans} from '#/main/app/intl/translation'
import {PropTypes as T, implementPropTypes} from '#/main/app/prop-types'
import {DataInput as DataInputTypes} from '#/main/app/data/types/prop-types'

const QuotaInput = props => {
  /*const [default, setDefault] = useState(props.value.default)
  const [years, setYears] = useState(props.value.years)*/
  const [def, setDef] = useState({
    enabled: false,
    quota: 0
  })
  const [years, setYears] = useState({})
  const [chooses, setChooses] = useState(Array.from({length:31}, (_, i) => 2020 + i).reduce((acc, year) => {
    return {
      ...acc,
      [year]: !years[year]
    }
  }, {}))
  const [choose, setChoose] = useState()

  useEffect(() => setChoose(Object.entries(chooses).find(entry => entry[1] && entry[0] != choose)[0]), [chooses])

  return (
    <Fragment>
      <ContentSections>
        <ContentSection title={trans('default')}>
          <ControlLabel>{trans('enabled')}</ControlLabel>
          <Checkbox name="quota_default_enabled" checked={def.enabled} onChange={e => setDef({
            enabled: e.target.checked,
            quota: def.quota
          })} />
          <ControlLabel>{trans('quota', {}, 'cursus')}</ControlLabel>
          <FormControl name="quota_default_quota" type="number" value={def.quota} onChange={e => setDef({
            enabled: def.enabled,
            quota: e.target.value
          })} />
        </ContentSection>

        {Object.entries(years).sort((a, b) => a[0] - b[0]).map(([year, {enabled, quota}]) =>
          <ContentSection key={year} title={`${year}`} actions={[
            {
              name: 'remove-year',
              type: CALLBACK_BUTTON,
              icon: 'fa fa-fw fa-trash',
              label: trans('remove_year'),
              callback: () => {
                const {[year]: _, ...restYears} = years
                setYears(restYears)
                setChooses({
                  ...chooses,
                  [year]: true
                })
              }
            }
          ]}>
            <ControlLabel>{trans('enabled')}</ControlLabel>
            <Checkbox name={`quota_${year}_enabled`} checked={enabled} onChange={e => setYears({
              ...years,
              [year]: {
                enabled: e.target.checked,
                quota
              }
            })} />
            <ControlLabel>{trans('quota', {}, 'cursus')}</ControlLabel>
            <FormControl name={`quota_${year}_quota`} type="number" value={quota} onChange={e => setYears({
              ...years,
              [year]: {
                enabled,
                quota: e.target.value
              }
            })} />
          </ContentSection>
        )}
      </ContentSections>

      <FormControl componentClass="select" value={choose} onChange={e => setChoose(e.target.value)}>
        {Object.entries(chooses).filter(c => c[1]).map(([year, _]) =>
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
          setYears({
            ...years,
            [choose]: def
          })
          setChooses({
            ...chooses,
            [choose]: false
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
