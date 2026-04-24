<?php

namespace App\Http\Controllers\Admin\ImportExport;

use App\Helpers\Editions;
use App\Http\Controllers\Controller;
use App\Support\ImportExportRunService;
use Illuminate\Http\Request;

class ExportAttendancesController extends Controller
{
    public function __invoke(Request $request, ImportExportRunService $runService)
    {
        $this->authorize('exportAttendances');

        if (Editions::reportingLocked()) {
            return to_route('admin.import-export.attendances')
                ->with('flash.banner', 'Advanced Reporting is an Enterprise Feature 🔒. Please Upgrade.')
                ->with('flash.bannerStyle', 'danger');
        }

        $validated = $request->validate([
            'month' => ['nullable', 'date'],
            'year' => ['nullable', 'integer'],
            'division' => ['nullable', 'integer'],
            'job_title' => ['nullable', 'integer'],
            'education' => ['nullable', 'integer'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $run = $runService->queueAttendanceExport($request->user(), [
            'month' => $validated['month'] ?? null,
            'year' => $validated['year'] ?? null,
            'division' => $validated['division'] ?? null,
            'job_title' => $validated['job_title'] ?? null,
            'education' => $validated['education'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
        ]);

        return to_route('admin.import-export.attendances')
            ->with('flash.banner', "Attendance export queued in background. Track progress from run #{$run->id}.")
            ->with('flash.bannerStyle', 'success');
    }
}
