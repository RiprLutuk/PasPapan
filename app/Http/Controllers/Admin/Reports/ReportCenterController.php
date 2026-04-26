<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\JobTitle;
use App\Models\Shift;

class ReportCenterController extends Controller
{
    public function __invoke()
    {
        $this->authorize('viewOperationalReports');

        return view('admin.reports.index', [
            'divisions' => Division::query()->orderBy('name')->get(['id', 'name']),
            'jobTitles' => JobTitle::query()->orderBy('name')->get(['id', 'name']),
            'shifts' => Shift::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
