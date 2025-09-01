import React, {Fragment} from 'react'
import {PropTypes as T} from 'prop-types'

import {trans} from '#/main/app/intl/translation'
import {LINK_BUTTON, CALLBACK_BUTTON, MODAL_BUTTON} from '#/main/app/buttons'
import {ListData} from '#/main/app/content/list/containers/data'
import {constants as listConst} from '#/main/app/content/list/constants'
import {route} from '#/main/core/user/routing'
import {UserCard} from '#/main/core/user/components/card'

import {Course as CourseTypes, Session as SessionTypes} from '#/plugin/cursus/prop-types'
import {selectors} from '#/plugin/cursus/tools/trainings/catalog/store/selectors'

const CourseCancellations = (props) =>
  <Fragment>
    <ListData
      name={selectors.STORE_NAME+'.sessionCancellation'}
      fetch={{
        url: ['apiv2_cursus_session_list_cancellations', {id: props.activeSession.id}],
        autoload: true
      }}
      delete={null}
      definition={[
        {
          name: 'user',
          type: 'user',
          label: trans('user'),
          displayed: true
        }, {
          name: 'organization',
          type: 'string',
          label: trans('organization'),
          displayed: true,
          sortable: false,
          filterable: false
        }, {
          name: 'user.email',
          type: 'email',
          label: trans('email'),
          displayed: true,
          sortable: false,
          filterable: false
        }, {
          name: 'date',
          type: 'date',
          label: trans('registration_date', {}, 'cursus'),
          options: {time: true},
          displayed: true
        }, {
          name: 'userDisabled',
          label: trans('user_disabled'),
          type: 'boolean',
          displayable: false,
          sortable: false,
          filterable: true
        }
      ]}
      primaryAction={(row) => ({
        type: LINK_BUTTON,
        target: route(row.user)
      })}
      actions={() => []}
      card={(cardProps) => <UserCard {...cardProps} data={cardProps.data.user} />}
      display={{
        current: listConst.DISPLAY_TABLE
      }}
    />
  </Fragment>

CourseCancellations.propTypes = {
  path: T.string.isRequired,
  course: T.shape(
    CourseTypes.propTypes
  ).isRequired,
  activeSession: T.shape(
    SessionTypes.propTypes
  )
}

export {
  CourseCancellations
}
