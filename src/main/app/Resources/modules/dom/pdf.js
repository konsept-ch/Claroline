let pdfLoader

/**
 * Lazily load html2pdf and its heavy dependencies (html2canvas + jsPDF).
 * The promise is memoized so repeated calls reuse the same chunk.
 */
export const loadHtml2Pdf = () => {
  if (!pdfLoader) {
    pdfLoader = Promise.all([
      import(/* webpackChunkName: "html2pdf" */ 'html2canvas'),
      import(/* webpackChunkName: "html2pdf" */ 'jspdf'),
      import(/* webpackChunkName: "html2pdf" */ 'html2pdf.js')
    ]).then(([html2canvasModule, jsPdfModule, html2pdfModule]) => {
      const html2canvas = html2canvasModule?.default || html2canvasModule
      const jsPdfExport = jsPdfModule?.default || jsPdfModule
      const jsPDF = jsPdfModule?.jsPDF || jsPdfExport?.jsPDF || jsPdfExport
      const html2pdf = html2pdfModule?.default || html2pdfModule

      if (typeof window !== 'undefined') {
        if (html2canvas && !window.html2canvas) {
          window.html2canvas = html2canvas
        }

        if (jsPDF && !window.jsPDF) {
          window.jsPDF = jsPDF
        }
      }

      return html2pdf
    })
  }

  return pdfLoader
}
