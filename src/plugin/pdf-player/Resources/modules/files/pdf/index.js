import React, {lazy, Suspense} from 'react'

const PdfPlayerLazy = lazy(() =>
  import(/* webpackChunkName: "plugin-pdf-file-pdf-player" */ '#/plugin/pdf-player/files/pdf/containers/player')
    .then((module) => ({default: module.default || module.PdfPlayer}))
)

const PdfPlaceholder = () =>
  <div className="pdf-player-placeholder" aria-hidden="true">
    <i className="fa fa-file-pdf-o" />
  </div>

const PdfPlayer = (props) =>
  <Suspense fallback={<PdfPlaceholder />}>
    <PdfPlayerLazy {...props} />
  </Suspense>

const fileType = {
  components: {
    player: PdfPlayer,
    placeholder: PdfPlaceholder
  },
  styles: ['claroline-distribution-plugin-pdf-player-pdf-resource']
}

export {
  fileType
}
