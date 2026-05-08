import React, {Component} from 'react'
import {PropTypes as T} from 'prop-types'
import get from 'lodash/get'
import cloneDeep from 'lodash/cloneDeep'

import {trans} from '#/main/app/intl/translation'
import {param} from '#/main/app/config'
import {CALLBACK_BUTTON} from '#/main/app/buttons'
import {Alert} from '#/main/app/alert/components/alert'
import {FormStepper} from '#/main/app/content/form/components/stepper'
import {notEmpty} from '#/main/app/data/types/validators'

import {Facet} from '#/main/app/security/registration/components/facet'
import {Required} from '#/main/app/security/registration/components/required'
import {Optional} from '#/main/app/security/registration/components/optional'
import {Organization} from '#/main/app/security/registration/components/organization'
import {Workspace} from '#/main/app/security/registration/components/workspace'
import {Registration} from '#/main/app/security/registration/components/registration'
import {OrganizationSelection} from '#/main/app/security/registration/components/organization-selection'

import {constants} from '#/main/app/security/registration/constants'
import {formatFormSections} from '#/main/core/user/profile/utils'

function isFieldDisplayed(field, data) {
  if ('function' === typeof field.displayed) {
    return field.displayed(data)
  }

  return undefined === field.displayed || field.displayed
}

function validateFields(fields, data) {
  return fields.every(field => {
    if (!isFieldDisplayed(field, data)) {
      return true
    }

    if (field.required && notEmpty(get(data, field.name))) {
      return false
    }

    if (field.linked && field.linked.length > 0) {
      return validateFields(field.linked, data)
    }

    return true
  })
}

function validateSections(sections, data) {
  return sections.every(section => validateFields(section.fields || [], data))
}

class RegistrationMain extends Component {
  componentDidMount() {
    this.props.fetchRegistrationData()
  }

  render() {
    let steps = []

    if (!this.props.options.allowWorkspace && this.props.defaultWorkspaces) {
      steps.push({
        title: 'Registration',
        component: Registration,
        validate: () => true
      })
    }

    const requiredFields = [
      {
        name: 'lastName',
        type: 'string',
        label: trans('last_name'),
        required: true
      }, {
        name: 'firstName',
        type: 'string',
        label: trans('first_name'),
        required: true
      }, {
        name: 'email',
        type: 'email',
        label: trans('email'),
        required: true
      }, {
        name: 'username',
        type: 'username',
        label: trans('username'),
        required: true,
        displayed: param('community.username')
      }, {
        name: 'plainPassword',
        type: 'password',
        label: trans('password'),
        required: true
      }
    ]

    steps = steps.concat([
      {
        title: trans('my_account'),
        component: Required,
        validate: () => validateFields(requiredFields, this.props.user)
      }, {
        title: 'Configuration',
        component: Optional,
        validate: () => true
      }
    ], this.props.facets.map(facet => {
      const formattedSections = facet.sections ?
        formatFormSections(cloneDeep(facet.sections), this.props.allFacetFields, this.props.user) :
        []

      return {
        title: facet.title,
        validate: () => validateSections(formattedSections, this.props.user),
        render: () => {
          const currentFacet = <Facet facet={facet} allFields={this.props.allFacetFields} user={this.props.user} />

          return currentFacet
        }
      }
    }))

    if (constants.ORGANIZATION_SELECTION_CREATE === this.props.options.organizationSelection) {
      const organizationFields = [
        {
          name: 'mainOrganization.name',
          type: 'string',
          label: trans('name'),
          required: true
        }, {
          name: 'mainOrganization.code',
          type: 'string',
          label: trans('code'),
          required: true
        }, {
          name: 'mainOrganization.vat',
          label: trans('vat_number'),
          type: 'string',
          required: false
        }, {
          name: 'mainOrganization.email',
          type: 'email',
          label: trans('email')
        }
      ]

      steps.push({
        title: trans('organization'),
        component: Organization,
        validate: () => validateFields(organizationFields, this.props.user)
      })
    } else if (constants.ORGANIZATION_SELECTION_SELECT === this.props.options.organizationSelection) {
      const organizationSelectionFields = [
        {
          name: 'mainOrganization',
          type: 'organization',
          label: trans('organization'),
          required: true,
          hideLabel: true,
          options: {
            mode: 'choice'
          }
        }
      ]

      steps.push({
        title: trans('organization'),
        component: OrganizationSelection,
        validate: () => validateFields(organizationSelectionFields, this.props.user)
      })
    }

    if (this.props.options.allowWorkspace) {
      steps.push({
        title: trans('workspaces'),
        component: Workspace,
        validate: () => true
      })
    }

    const canSubmit = steps.every(step => !step.validate || step.validate())

    return (
      <FormStepper
        submit={{
          type: CALLBACK_BUTTON,
          label: trans('create-account', {}, 'actions'),
          disabled: !canSubmit,
          confirm: {
            title: trans('registration'),
            message: trans('register_confirm_message'),
            button: trans('registration_confirm'),
            additional: constants.REGISTRATION_MAIL_VALIDATION_NONE !== this.props.options.validation ? (
              <div className="modal-body">
                <Alert type="info">
                  {trans('registration_mail_help')}
                </Alert>

                {constants.REGISTRATION_MAIL_VALIDATION_FULL === this.props.options.validation &&
                  <Alert type="warning">
                    {trans('registration_validation_help')}
                  </Alert>
                }
              </div>
            ) : undefined
          },
          callback: () => {
            if (!canSubmit) {
              return
            }

            this.props.register(this.props.user, this.props.termOfService, (user) => {
              this.props.onRegister(user)
            })
          }
        }}
        steps={steps}
      />
    )
  }
}

RegistrationMain.propTypes = {
  path: T.string,
  history: T.shape({
    push: T.func.isRequired
  }).isRequired,
  location: T.shape({
    path: T.string
  }),
  user: T.shape({
    // user type
  }).isRequired,
  facets: T.arrayOf(T.shape({
    id: T.string.isRequired,
    title: T.string.isRequired
  })),
  termOfService: T.string,
  register: T.func.isRequired,
  fetchRegistrationData: T.func.isRequired,
  options: T.shape({
    validation: T.bool,
    allowWorkspace: T.bool,
    organizationSelection: T.string
  }).isRequired,
  defaultWorkspaces: T.array,
  allFacetFields: T.array,
  onRegister: T.func
}

export {
  RegistrationMain
}
