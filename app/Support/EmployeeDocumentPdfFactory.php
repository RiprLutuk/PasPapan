<?php

namespace App\Support;

use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Dompdf\Canvas;
use Dompdf\FontMetrics;

class EmployeeDocumentPdfFactory
{
    /**
     * @param  array<string, string>  $documentMeta
     */
    public function previewHtml(
        string $body,
        ?string $footer,
        array $documentMeta = [],
        array $layoutOptions = [],
    ): string {
        return view('pdf.employee-document-template', [
            'body' => $body,
            'footer' => $footer,
            'companyName' => Setting::getValue('app.company_name', config('app.name')),
            'documentMeta' => $documentMeta,
            'layoutOptions' => $layoutOptions,
            'preview' => true,
        ])->render();
    }

    /**
     * @param  array<string, string>  $documentMeta
     */
    public function make(
        string $body,
        ?string $footer,
        ?string $paperSize = 'a4',
        ?string $orientation = 'portrait',
        array $documentMeta = [],
        array $layoutOptions = [],
    ): DomPdfWrapper {
        $pdf = Pdf::loadView('pdf.employee-document-template', [
            'body' => $body,
            'footer' => $footer,
            'companyName' => Setting::getValue('app.company_name', config('app.name')),
            'documentMeta' => $documentMeta,
            'layoutOptions' => $layoutOptions,
        ])->setPaper($paperSize ?: 'a4', $orientation ?: 'portrait');

        $pdf->getDomPDF()->setCallbacks([
            [
                'event' => 'end_document',
                'f' => static function (
                    int $pageNumber,
                    int $pageCount,
                    Canvas $canvas,
                    FontMetrics $fontMetrics,
                ): void {
                    if ($pageCount <= 2) {
                        return;
                    }

                    $font = $fontMetrics->getFont('DejaVu Sans');
                    $label = __('Page :page of :pages', [
                        'page' => $pageNumber,
                        'pages' => $pageCount,
                    ]);
                    $fontSize = 8;
                    $width = $fontMetrics->getTextWidth($label, $font, $fontSize);

                    $canvas->text(
                        $canvas->get_width() - 56 - $width,
                        $canvas->get_height() - 31,
                        $label,
                        $font,
                        $fontSize,
                        [0.42, 0.45, 0.50],
                    );
                },
            ],
        ]);

        return $pdf;
    }
}
