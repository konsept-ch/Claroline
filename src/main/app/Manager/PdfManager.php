<?php

namespace Claroline\AppBundle\Manager;

use Claroline\AppBundle\Manager\File\TempFileManager;
use Dompdf\Dompdf;
use Twig\Environment;

class PdfManager
{
    /** @var Environment */
    private $templating;
    /** @var TempFileManager */
    private $tempFileManager;
    /** @var PlatformManager */
    private $platformManager;

    public function __construct(
        Environment $templating,
        TempFileManager $tempFileManager,
        PlatformManager $platformManager
    ) {
        $this->templating = $templating;
        $this->tempFileManager = $tempFileManager;
        $this->platformManager = $platformManager;
    }

    public function fromHtml(string $htmlContent, string $layout = '@ClarolineApp/pdf.html.twig', array $options = []): ?string
    {
        $domPdf = new Dompdf([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'tempDir' => $this->tempFileManager->getDirectory(),
            'fontDir' => $this->tempFileManager->getDirectory(),
            'fontCache' => $this->tempFileManager->getDirectory(),
        ]);

        $domPdf->loadHtml($this->templating->render($layout, [
            'baseUrl' => $this->platformManager->getUrl(),
            'content' => $htmlContent,
        ]));

        $paperSize = $options['paper_size'] ?? 'A4';
        $paperOrientation = $options['paper_orientation'] ?? 'portrait';
        $domPdf->setPaper($paperSize, $paperOrientation);

        // Render the HTML as PDF
        $domPdf->render();

        return $domPdf->output();
    }

    public function saveFromHtml(string $htmlContent, string $layout, array $options = []): ?string
    {
        $filename = $this->tempFileManager->generate();
        file_put_contents($filename, $this->fromHtml($htmlContent, $layout, $options));
        return $filename;
    }
}
