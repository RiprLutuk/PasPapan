<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Exports\LeaveRequestsExport;
use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportLeaveReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $this->authorize('manageLeaveApprovals');

        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'approval_status' => ['nullable', 'in:all,pending,approved,rejected'],
            'request_type' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $value = (string) $value;

                    if (in_array($value, ['all', 'leave', 'permission', 'sick', 'excused', 'rejected'], true)) {
                        return;
                    }

                    if (ctype_digit($value) && LeaveType::query()->whereKey((int) $value)->exists()) {
                        return;
                    }

                    $fail(__('The selected request type is invalid.'));
                },
            ],
            'division' => ['nullable', 'integer', 'exists:divisions,id'],
            'job_title' => ['nullable', 'integer', 'exists:job_titles,id'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $filename = 'leave-report-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new LeaveRequestsExport($request->user(), $validated), $filename);
    }
}
