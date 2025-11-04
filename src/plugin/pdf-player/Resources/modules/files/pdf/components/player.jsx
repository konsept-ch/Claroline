import React, {Component, Fragment} from 'react'
import {PropTypes as T} from 'prop-types'

import {url} from '#/main/app/api'
import {trans, transChoice} from '#/main/app/intl/translation'
import {ContentLoader} from '#/main/app/content/components/loader'
import {Button} from '#/main/app/action/components/button'
import {CALLBACK_BUTTON, URL_BUTTON} from '#/main/app/buttons'

import {File as FileTypes} from '#/main/core/files/prop-types'

let pdfDependenciesPromise = null

function loadPdfDependencies() {
  if (!pdfDependenciesPromise) {
    pdfDependenciesPromise = Promise.all([
      import(/* webpackChunkName: "pdfjs-viewer" */ 'pdfjs-dist/legacy/build/pdf'),
      import(/* webpackChunkName: "pdfjs-viewer" */ 'pdfjs-dist/legacy/build/pdf.worker.entry'),
      import(/* webpackChunkName: "pdfjs-viewer" */ 'pdfjs-dist/legacy/web/pdf_viewer')
    ]).then(([pdfModule, workerModule, viewerModule]) => {
      const pdfjsLib = pdfModule?.default || pdfModule
      const workerSrc = workerModule?.default || workerModule
      const viewerExports = viewerModule?.default || viewerModule
      const {EventBus, PDFLinkService, PDFSinglePageViewer} = viewerExports

      if (pdfjsLib?.GlobalWorkerOptions) {
        pdfjsLib.GlobalWorkerOptions.workerSrc = workerSrc
      }

      return {
        pdfjsLib,
        EventBus,
        PDFLinkService,
        PDFSinglePageViewer
      }
    })
  }

  return pdfDependenciesPromise
}

class PdfPlayer extends Component {
  constructor(props) {
    super(props)

    this.state = {
      loaded: false,
      pdf: null,
      viewer: null,
      page: 1,
      scale: 100,
      height: null
    }

    this.resize = this.resize.bind(this)
    this._isMounted = false
    this.loadingTask = null
  }

  componentDidMount() {
    this._isMounted = true

    const container = document.getElementById('pdf-' + this.props.file.id)
    if (!container) {
      return
    }

    loadPdfDependencies().then((deps) => {
      if (!this._isMounted) {
        return
      }

      const {EventBus, PDFLinkService, PDFSinglePageViewer, pdfjsLib} = deps

      const eventBus = new EventBus()

      // enable hyperlinks within PDF files.
      const pdfLinkService = new PDFLinkService({eventBus})

      // PDFViewer
      const pdfSinglePageViewer = new PDFSinglePageViewer({
        container,
        eventBus,
        linkService: pdfLinkService
      })
      pdfLinkService.setViewer(pdfSinglePageViewer)

      eventBus.on('pagesinit', () => {
        this.resize(pdfSinglePageViewer)
        this.renderPage(1)
      })

      eventBus.on('pagechanging', (event) => this.renderPage(event.pageNumber))

      this.loadingTask = pdfjsLib.getDocument({
        url: this.props.file.url
      })

      this.loadingTask.promise.then((pdf) => {
        if (!this._isMounted) {
          return
        }

        pdfSinglePageViewer.setDocument(pdf)
        pdfLinkService.setDocument(pdf, null)

        this.setState({
          loaded: true,
          pdf: pdf,
          viewer: pdfSinglePageViewer
        })
      })
    })
  }

  componentWillUnmount() {
    this._isMounted = false

    if (this.loadingTask && typeof this.loadingTask.destroy === 'function') {
      this.loadingTask.destroy()
    }

    this.loadingTask = null
  }

  resize(pdfViewer) {
    if (!pdfViewer) {
      return
    }

    pdfViewer.currentScaleValue = this.state.scale / 100

    this.setState({
      height: pdfViewer.viewer.offsetHeight
    })
  }

  renderPage(pageNumber) {
    this.setState({page: parseInt(pageNumber, 10)})

    if (this.props.currentUser && this.state.pdf) {
      this.props.updateProgression(this.props.file.id, pageNumber, this.state.pdf.numPages)
    }
  }

  changePage(pdfViewer, requestPageNum) {
    let pageNum = requestPageNum

    if (!pageNum || 1 >= pageNum) {
      pageNum = 1
    } else if (pageNum > this.state.pdf.numPages) {
      pageNum = this.state.pdf.numPages
    }

    if (pdfViewer) {
      pdfViewer.currentPageNumber = parseInt(pageNum, 10)
    }
  }

  zoom(requestScale) {
    let scale = requestScale
    if (1 >= scale) {
      scale = 1
    }

    this.setState({scale: parseInt(scale, 10)}, () => this.resize(this.state.viewer))
  }

  render() {
    return (
      <Fragment>
        {!this.state.loaded &&
          <ContentLoader
            className="row"
            size="lg"
            description={trans('loading', {}, 'file')}
          />
        }

        {this.state.loaded &&
          <div className="row">
            <div className="pdf-menu">
              <div className="pdf-pages">
                <Button
                  className="btn btn-link"
                  type={CALLBACK_BUTTON}
                  icon="fa fa-fw fa-backward"
                  label={trans('previous')}
                  disabled={!this.state.page || 1 >= this.state.page}
                  callback={() => this.changePage(this.state.viewer, this.state.page - 1)}
                  tooltip="bottom"
                />

                <Button
                  className="btn btn-link"
                  type={CALLBACK_BUTTON}
                  icon="fa fa-fw fa-forward"
                  label={trans('next')}
                  disabled={!this.state.pdf || !this.state.page || this.state.pdf.numPages <= this.state.page}
                  callback={() => this.changePage(this.state.viewer, this.state.page + 1)}
                  tooltip="bottom"
                />

                <input
                  type="number"
                  className="form-control input-sm"
                  value={this.state.page}
                  onChange={(e) => this.changePage(this.state.viewer, e.currentTarget.value)}
                />
                {transChoice('count_pages', this.state.pdf ? this.state.pdf.numPages : 0, {count: this.state.pdf ? this.state.pdf.numPages : 0}, 'resource')}
              </div>

              <div className="pdf-zoom">
                <Button
                  className="btn btn-link"
                  type={CALLBACK_BUTTON}
                  icon="fa fa-fw fa-search-plus"
                  label={trans('zoom_in')}
                  callback={() => this.zoom(this.state.scale + 25)}
                  disabled={!this.state.pdf || !this.state.scale}
                  tooltip="bottom"
                />

                <Button
                  className="btn btn-link"
                  type={CALLBACK_BUTTON}
                  icon="fa fa-fw fa-search-minus"
                  label={trans('zoom_out')}
                  callback={() => this.zoom(this.state.scale - 25)}
                  disabled={!this.state.pdf || !this.state.scale || 1 >= this.state.scale}
                  tooltip="bottom"
                />

                <input
                  type="number"
                  min="5"
                  className="form-control input-sm"
                  value={this.state.scale}
                  onChange={(e) => this.zoom(e.currentTarget.value)}
                />
                <span className="pdf-zoom-unit">%</span>

                <Button
                  className="btn btn-link"
                  type={URL_BUTTON}
                  icon="fa fa-fw fa-file-download"
                  label={trans('download', {}, 'actions')}
                  target={url(['claro_resource_download'], {ids: [this.props.nodeId]})}
                  disabled={!this.state.pdf}
                  tooltip="bottom"
                />
              </div>
            </div>
          </div>
        }

        <div
          className="pdf-container component-container"
          style={!this.state.loaded ? {display: 'none'} : {height: this.state.height}}
        >
          <div
            id={'pdf-' + this.props.file.id}
            className="pdf"
            style={{
              position: 'absolute',
              overflow: 'hidden',
              width: '100%',
              height: '100%'
            }}
          >
            <div id="viewer" className="pdfViewer" />
          </div>
        </div>
      </Fragment>
    )
  }
}

PdfPlayer.propTypes = {
  nodeId: T.string.isRequired,
  file: T.shape(
    FileTypes.propTypes
  ).isRequired,
  updateProgression: T.func.isRequired,
  currentUser: T.object
}

export {
  PdfPlayer
}
