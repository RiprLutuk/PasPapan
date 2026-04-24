<?php

namespace App\Http\Controllers\Admin\ImportExport;

use App\Helpers\Editions;
use App\Http\Controllers\Controller;
use App\Support\ImportExportRunService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImportUsersController extends Controller
{
    public function __invoke(Request $request, ImportExportRunService $runService): RedirectResponse
    {
        $this->authorize('importUsers');

        if (Editions::reportingLocked()) {
            return to_route('admin.import-export.users')
                ->with('flash.banner', __('This feature is available in the Enterprise Edition. Please upgrade.'))
                ->with('flash.bannerStyle', 'danger');
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xls,xlsx,ods', 'max:10240'],
        ]);

        $run = $runService->queueUsersImport($request->user(), $validated['file']);

        return to_route('admin.import-export.users')
            ->with('flash.banner', "User import queued in background. Track progress from run #{$run->id}.")
            ->with('flash.bannerStyle', 'success');
    }
}
