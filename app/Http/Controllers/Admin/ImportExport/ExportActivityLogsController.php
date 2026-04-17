<?php

namespace App\Http\Controllers\Admin\ImportExport;

use App\Contracts\ReportingServiceInterface;
use App\Http\Controllers\Admin\ImportExport\Concerns\HandlesServiceResponse;
use App\Http\Controllers\Controller;

class ExportActivityLogsController extends Controller
{
    use HandlesServiceResponse;

    public function __invoke(ReportingServiceInterface $reportingService)
    {
        return $this->handleServiceResponse(
            $reportingService->exportActivityLogs()
        );
    }
}
