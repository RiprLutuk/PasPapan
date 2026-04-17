<?php

namespace App\Http\Controllers\Admin\ImportExport;

use App\Contracts\ReportingServiceInterface;
use App\Http\Controllers\Admin\ImportExport\Concerns\HandlesServiceResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExportReportPdfController extends Controller
{
    use HandlesServiceResponse;

    public function __invoke(Request $request, ReportingServiceInterface $reportingService)
    {
        return $this->handleServiceResponse(
            $reportingService->exportMonthlyReportPdf(
                $request->input('month', now()->month),
                $request->input('year', now()->year),
            )
        );
    }
}
