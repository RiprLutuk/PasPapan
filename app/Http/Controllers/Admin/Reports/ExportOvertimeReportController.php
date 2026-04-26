<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Exports\OvertimeRequestsExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportOvertimeReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $this->authorize('manageOvertime');

        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', 'in:all,pending,approved,rejected'],
            'division' => ['nullable', 'integer', 'exists:divisions,id'],
            'job_title' => ['nullable', 'integer', 'exists:job_titles,id'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $filename = 'overtime-report-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new OvertimeRequestsExport($request->user(), $validated), $filename);
    }
}
