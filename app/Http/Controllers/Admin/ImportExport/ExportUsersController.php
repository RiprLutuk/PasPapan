<?php

namespace App\Http\Controllers\Admin\ImportExport;

use App\Contracts\ReportingServiceInterface;
use App\Http\Controllers\Admin\ImportExport\Concerns\HandlesServiceResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExportUsersController extends Controller
{
    use HandlesServiceResponse;

    public function __invoke(Request $request, ReportingServiceInterface $reportingService)
    {
        $groups = $request->input('groups', ['user']);
        if (is_string($groups)) {
            $groups = explode(',', $groups);
        }

        return $this->handleServiceResponse(
            $reportingService->exportUsers($groups)
        );
    }
}
