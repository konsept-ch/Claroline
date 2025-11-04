import React, {Suspense} from 'react'
import {PropTypes as T} from 'prop-types'

import {url} from '#/main/app/api/router'
import {trans} from '#/main/app/intl/translation'
import {LINK_BUTTON} from '#/main/app/buttons'
import {ToolPage} from '#/main/core/tool/containers/page'

const SwaggerUI = React.lazy(() => import(/* webpackChunkName: "swagger-ui" */ 'swagger-ui-react'))

const ApiAdministration = (props) =>
  <ToolPage
    path={[{
      type: LINK_BUTTON,
      label: trans('api', {}, 'integration'),
      target: `${props.path}/api`
    }]}
    subtitle={trans('api', {}, 'integration')}
    actions={[]}
  >
    <Suspense fallback={<div>{trans('loading')}</div>}>
      <SwaggerUI
        url={url(['apiv2_swagger_get'])}
      />
    </Suspense>
  </ToolPage>

ApiAdministration.propTypes = {
  path: T.string.isRequired
}

export {
  ApiAdministration
}
