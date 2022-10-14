import React from 'react'
import {PropTypes as T} from 'prop-types'

import {trans} from '#/main/app/intl/translation'
import {LINK_BUTTON} from '#/main/app/buttons'
import {FormData} from '#/main/app/content/form/containers/data'

import {Quota as QuotaTypes} from '#/plugin/cursus/prop-types'

const QuotaForm = (props) => {
  console.log('FORM', props.quota)

  return <FormData
    name={props.name}
    meta={false}
    buttons={true}
    target={(data, isNew) => isNew ?
      ['apiv2_cursus_quota_create'] :
      ['apiv2_cursus_quota_update', {id: data.id}]
    }
    cancel={{
      type: LINK_BUTTON,
      target: props.path,
      exact: true
    }}
    sections={[
      {
        title: trans('general'),
        primary: true,
        fields: [
          {
            name: 'organization',
            type: 'organization',
            label: trans('organization'),
            required: true,
            options: {
              url: 'apiv2_cursus_quota_organizations'
            }
          }
        ]
      }, {
        title: trans('quotas'),
        fields: [
          {
            name: 'options',
            type: 'quota',
            label: trans('annual_exercices'),
            required: false,
            onChange: (options) => {
              props.update(props.name, 'options', options)
            }
          }
        ]
      }
    ]}
  />
}

QuotaForm.propTypes = {
  path: T.string.isRequired,
  name: T.string.isRequired,

  // from store
  isNew: T.bool.isRequired,
  quota: T.shape(
    QuotaTypes.propTypes
  ),
  update: T.func.isRequired
}

export {
  QuotaForm
}