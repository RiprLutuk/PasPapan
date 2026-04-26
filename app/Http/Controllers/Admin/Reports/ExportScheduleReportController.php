<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Exports\ScheduleRosterExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportScheduleReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $this->authorize('manageSchedules');

        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'division' => ['nullable', 'integer', 'exists:divisions,id'],
            'job_title' => ['nullable', 'integer', 'exists:job_titles,id'],
            'shift_id' => ['nullable', 'integer', 'exists:shifts,id'],
            'off_status' => ['nullable', 'in:all,working,off'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $filename = 'schedule-roster-report-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new ScheduleRosterExport($request->user(), $validated), $filename);
    }
}
