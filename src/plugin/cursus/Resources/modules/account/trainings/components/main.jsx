import React from 'react'
import {PropTypes as T} from 'prop-types'

import {trans} from '#/main/app/intl/translation'
import {LINK_BUTTON} from '#/main/app/buttons'
import {showBreadcrumb} from '#/main/app/layout/utils'

import {UserPage} from '#/main/core/user/components/page'
import {User as UserTypes} from '#/main/core/user/prop-types'

import {SessionHistory} from '#/plugin/cursus/tools/trainings/session/components/history'

const AccountTrainingsMain = (props) =>
  <UserPage
    showBreadcrumb={showBreadcrumb()}
    breadcrumb={[
      {
        type: LINK_BUTTON,
        label: trans('my_account'),
        target: '/account'
      }, {
        type: LINK_BUTTON,
        label: trans('my_courses', {}, 'cursus'),
        target: '/account/trainings'
      }
    ]}
    title={trans('my_courses', {}, 'cursus')}
    user={props.currentUser}
  >
    <div style={{
      marginTop: 60
    }}>
      <SessionHistory
        path="/account/trainings"
        sessionPath="/desktop/trainings/catalog"
        invalidateList={props.invalidateList}
      />
    </div>
  </UserPage>

AccountTrainingsMain.propTypes = {
  currentUser: T.shape(
    UserTypes.propTypes
  ).isRequired,
  invalidateList: T.func.isRequired
}

export {
  AccountTrainingsMain
}
