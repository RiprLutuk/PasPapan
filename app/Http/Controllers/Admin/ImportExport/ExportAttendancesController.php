<?php

namespace App\Http\Controllers\Admin\ImportExport;

use App\Contracts\ReportingServiceInterface;
use App\Http\Controllers\Admin\ImportExport\Concerns\HandlesServiceResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExportAttendancesController extends Controller
{
    use HandlesServiceResponse;

    public function __invoke(Request $request, ReportingServiceInterface $reportingService)
    {
        return $this->handleServiceResponse(
            $reportingService->exportAttendances(
                $request->input('month'),
                $request->input('year'),
                $request->input('division'),
                $request->input('job_title'),
                $request->input('education'),
            )
        );
    }
}
