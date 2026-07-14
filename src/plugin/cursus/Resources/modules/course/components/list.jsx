import React from 'react'
import {PropTypes as T} from 'prop-types'

import {trans} from '#/main/app/intl/translation'
import {param} from '#/main/app/config'
import {hasPermission} from '#/main/app/security'
import {LINK_BUTTON, MODAL_BUTTON, URL_BUTTON} from '#/main/app/buttons'
import {ListData} from '#/main/app/content/list/containers/data'
import {constants as listConst} from '#/main/app/content/list/constants'

import {route} from '#/plugin/cursus/routing'
import {CourseCard} from '#/plugin/cursus/course/components/card'
import {MODAL_DUPLICATE_COURSE} from '#/plugin/cursus/modals/duplicate'

const CourseList = (props) =>
  <ListData
    name={props.name}
    fetch={{
      url: props.url,
      autoload: true
    }}
    delete={{
      url: ['apiv2_cursus_course_delete_bulk'],
      displayed: (rows) => -1 !== rows.findIndex(course => hasPermission('delete', course))
    }}
    primaryAction={(row) => ({
      type: LINK_BUTTON,
      label: trans('open', {}, 'actions'),
      target: route(props.path, row)
    })}
    definition={[
      {
        name: 'name',
        type: 'string',
        label: trans('name'),
        displayed: true,
        primary: true
      }, {
        name: 'code',
        type: 'string',
        label: trans('code'),
        displayed: true
      }, {
        name: 'location',
        type: 'location',
        label: trans('location'),
        placeholder: trans('online_session', {}, 'cursus'),
        displayable: false,
        sortable: false
      }, {
        name: 'tags',
        type: 'tag',
        label: trans('tags'),
        displayed: true,
        sortable: false,
        options: {
          objectClass: 'Claroline\\CursusBundle\\Entity\\Course'
        }
      }, {
        name: 'pricing.price',
        alias: 'price',
        label: trans('price'),
        type: 'currency',
        displayable: param('pricing.enabled'),
        displayed: param('pricing.enabled'),
        filterable: param('pricing.enabled'),
        sortable: param('pricing.enabled')
      }, {
        name: 'display.order',
        alias: 'order',
        type: 'number',
        label: trans('order'),
        displayable: false,
        filterable: false
      }
    ]}
    actions={(rows) => [
      {
        name: 'edit',
        type: LINK_BUTTON,
        icon: 'fa fa-fw fa-pencil',
        label: trans('edit', {}, 'actions'),
        target: route(props.path, rows[0]) + '/edit',
        displayed: hasPermission('edit', rows[0]),
        group: trans('management'),
        scope: ['object']
      }, {
        name: 'duplicate',
        type: MODAL_BUTTON,
        icon: 'fa fa-fw fa-clone',
        label: trans('duplicate', {}, 'actions'),
        displayed: hasPermission('edit', rows[0]),
        group: trans('management'),
        modal: [MODAL_DUPLICATE_COURSE, {
          course: rows[0]
        }],
        scope: ['object']
      }, {
        name: 'export-pdf',
        type: URL_BUTTON,
        icon: 'fa fa-fw fa-file-pdf-o',
        label: trans('export-pdf', {}, 'actions'),
        displayed: hasPermission('open', rows[0]),
        group: trans('transfer'),
        target: ['apiv2_cursus_course_download_pdf', {id: rows[0].id}],
        scope: ['object']
      }
    ]}
    card={CourseCard}
    display={{
      current: listConst.DISPLAY_TABLE
    }}
  />

CourseList.propTypes = {
  path: T.string.isRequired,
  name: T.string.isRequired,
  url: T.oneOfType([T.string, T.array])
}

CourseList.defaultProps = {
  url: ['apiv2_cursus_course_list']
}

export {
  CourseList
}
