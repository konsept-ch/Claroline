import get from 'lodash/get'
import isEmpty from 'lodash/isEmpty'
import cloneDeep from 'lodash/cloneDeep'

import {trans} from '#/main/app/intl/translation'
import {hasPermission} from '#/main/app/security/permissions'
import {param} from '#/main/app/config'

function getMainFacet(facets) {
  return facets.find(facet => facet.meta.main)
}

function getDefaultFacet() {
  return {
    id: 'main',
    title: trans('general'),
    position: 0,
    meta: {
      main: true
    },
    sections: []
  }
}

function getDetailsDefaultSection(parameters, user) {
  return {
    id: 'default-props',
    title: trans('general'),
    primary: true,
    fields: [
      {
        name: 'email',
        type: 'email',
        label: trans('email'),
        displayed: !isEmpty(user.email)
      }, {
        name: 'phone',
        type: 'string',
        label: trans('phone'),
        displayed: !isEmpty(user.email)
      }, {
        name: 'meta.description',
        type: 'html',
        label: trans('description'),
        options: {
          minRows: 5
        }
      }
    ]
  }
}

function getFormDefaultSections(user, isNew = false) {
  return [
    {
      id: 'default-props',
      title: trans('general'),
      primary: true,
      fields: [
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
          name: 'phone',
          type: 'string',
          label: trans('phone')
        }, {
          name: 'username',
          type: 'username',
          label: trans('username'),
          required: true,
          displayed: param('community.username'),
          disabled: !isNew && !hasPermission('administrate', user)
        }, {
          name: 'plainPassword',
          type: 'password',
          label: trans('password'),
          displayed: isNew,
          required: true
        }, {
          name: 'meta.description',
          type: 'html',
          label: trans('description'),
          options: {
            minRows: 5
          }
        }, {
          name: 'meta.locale',
          type: 'locale',
          label: trans('language'),
          required: true,
          options: {
            onlyEnabled: true
          }
        }, {
          name: 'picture',
          type: 'image',
          label: trans('picture')
        }
      ]
    }, {
      icon: 'fa fa-fw fa-desktop',
      title: trans('display_parameters'),
      fields: [
        {
          name: 'poster',
          label: trans('poster'),
          type: 'image'
        }, {
          name: 'thumbnail',
          label: trans('thumbnail'),
          type: 'image'
        }
      ]
    }
  ]
}

function formatFormSections(sections, allFields, userData, params = {}, currentUser = null) {
  const hasConfidentialRights = currentUser ? hasRoles(currentUser.roles, ['ROLE_ADMIN'].concat(params.roles_confidential)): false
  const hasLockedRights = currentUser ? hasRoles(currentUser.roles, ['ROLE_ADMIN'].concat(params.roles_locked)): false

  return sections.map(section => {
    section.fields = section.fields
      .filter(f => !get(f, 'restrictions.hidden') && (hasConfidentialRights || !get(f, 'restrictions.metadata') || (currentUser && currentUser.id === userData.id)))
      .map(f => {
        if (!hasLockedRights && (
          (f.restrictions.locked && !f.restrictions.lockedEditionOnly) ||
          (f.restrictions.locked && f.restrictions.lockedEditionOnly && null !== get(userData, `profile.${f.id}`, null))
        )) {
          f.disabled = true
        }

        return formatField(f, allFields, 'profile')
      })

    return section
  })
}

function formatDetailsSections(sections, allFields, user, params, currentUser) {
  const hasConfidentialRights = currentUser ? hasRoles(currentUser.roles, ['ROLE_ADMIN'].concat(params['roles_confidential'])) : false

  return sections.map(section => {
    section.fields = section.fields
      .filter(f => !f.restrictions.hidden && (hasConfidentialRights || !f.restrictions.metadata || (currentUser && currentUser.id === user.id)))
      .map(f => formatField(f, allFields, 'profile'))

    return section
  })
}

function formatField(fieldDef, allFields, dataProp) {
  const field = {
    name: `${dataProp}.${fieldDef.id}`,
    type: fieldDef.type,
    label: fieldDef.label,
    required: fieldDef.required,
    help: fieldDef.help,
    options: fieldDef.options ? cloneDeep(fieldDef.options) : {},
    displayed: (data) => isFieldDisplayed(fieldDef, allFields, data[dataProp])
  }

  if (fieldDef.type === 'choice') {
    field.options.choices = fieldDef.options.choices ?
      fieldDef.options.choices.reduce((acc, choice) => Object.assign(acc, {
        [choice.value]: choice.value
      }), {}) : {}
  }

  return field
}

// CEP : un champ « organisation » a pour valeur l'organisation entière ({id, name, code, ...}),
// jamais égale (===) à la valeur de la condition. On compare alors les identifiants.
function getComparableValue(value) {
  if (value && 'object' === typeof value && !Array.isArray(value) && value.id) {
    return value.id
  }

  return value
}

function isFieldDisplayed(fieldDef, allFields, data, depth = 0) {
  if (!isEmpty(get(fieldDef, 'display.condition.field'))) {
    const parentField = allFields.find(f => f.id === fieldDef.display.condition.field)
    if (parentField) {
      // CEP : un champ dont le champ parent est masqué est masqué lui aussi. Sans cela, les
      // coordonnées d'une entreprise non référencée restaient affichées après le choix d'un
      // employeur dans la liste, ou d'un compte privé (constaté sur VAL le 27.11.2026).
      if (depth < 10 && !isFieldDisplayed(parentField, allFields, data, depth + 1)) {
        return false
      }

      const parentValue = getComparableValue(get(data, parentField.id))
      const expectedValue = getComparableValue(fieldDef.display.condition.value)

      let displayed = false
      switch (fieldDef.display.condition.comparator) {
        case 'equal':
          displayed = parentValue === expectedValue
          break
        case 'different':
          displayed = parentValue !== expectedValue
          break
        case 'empty':
          displayed = isEmpty(parentValue)
          break
        case 'not_empty':
          displayed = !isEmpty(parentValue)
          break
      }

      return displayed
    }
  }

  return true
}

// should be declared elsewhere
function hasRoles(roles, validRoleNames) {
  const validRoles = roles.filter(r => r.type === 1).filter(r => validRoleNames.indexOf(r.name) > -1)

  return validRoles.length > 0
}

export {
  getDetailsDefaultSection,
  getFormDefaultSections,
  getMainFacet,
  getDefaultFacet,
  formatFormSections,
  formatDetailsSections,
  formatField,
  isFieldDisplayed
}
