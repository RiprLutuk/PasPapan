<?php

namespace App\Http\Controllers\Admin\ImportExport;

use App\Helpers\Editions;
use App\Http\Controllers\Controller;
use App\Support\ImportExportRunService;
use Illuminate\Http\Request;

class ExportReportPdfController extends Controller
{
    public function __invoke(Request $request, ImportExportRunService $runService)
    {
        $this->authorize('exportAdminReports');

        if (Editions::reportingLocked()) {
            return to_route('admin.dashboard')
                ->with('flash.banner', 'Advanced Reporting is an Enterprise Feature 🔒. Please Upgrade.')
                ->with('flash.bannerStyle', 'danger');
        }

        $validated = $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
        ]);

        $run = $runService->queueMonthlyAttendanceReport(
            $request->user(),
            (int) ($validated['month'] ?? now()->month),
            (int) ($validated['year'] ?? now()->year),
        );

        return to_route('admin.dashboard')
            ->with('flash.banner', "Monthly report export queued in background. Track progress from run #{$run->id}.")
            ->with('flash.bannerStyle', 'success');
    }
}
