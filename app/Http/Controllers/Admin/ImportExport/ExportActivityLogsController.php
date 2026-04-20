<?php

namespace App\Http\Controllers\Admin\ImportExport;

use App\Helpers\Editions;
use App\Http\Controllers\Controller;
use App\Support\ImportExportRunService;
use Illuminate\Http\Request;

class ExportActivityLogsController extends Controller
{
    public function __invoke(Request $request, ImportExportRunService $runService)
    {
        $this->authorize('exportActivityLogs');

        if (Editions::auditLocked()) {
            return to_route('admin.activity-logs')
                ->with('flash.banner', 'Audit Logs Export is an Enterprise Feature 🔒. Please Upgrade.')
                ->with('flash.bannerStyle', 'danger');
        }

        $validated = $request->validate([
            'search' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $run = $runService->queueActivityLogExport($request->user(), $validated);

        return to_route('admin.activity-logs')
            ->with('flash.banner', "Activity log export queued in background. Track progress from run #{$run->id}.")
            ->with('flash.bannerStyle', 'success');
    }
}
