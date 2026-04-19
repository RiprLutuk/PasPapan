<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Appraisal;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;

class AppraisalExportPdfController extends Controller
{
    public function __invoke(Appraisal $appraisal)
    {
        $this->authorize('exportPdf', $appraisal);

        $appraisal->load(['user.division', 'user.jobTitle', 'evaluator', 'calibrator', 'evaluations.kpiTemplate']);

        $companyName = Setting::getValue('app.company_name', config('app.name'));
        $pdf = Pdf::loadView('pdf.appraisal-report', compact('appraisal', 'companyName'));

        return $pdf->download("appraisal-{$appraisal->user->name}-{$appraisal->period_month}-{$appraisal->period_year}.pdf");
    }
}
