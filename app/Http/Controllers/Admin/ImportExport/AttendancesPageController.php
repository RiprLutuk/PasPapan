<?php

namespace App\Http\Controllers\Admin\ImportExport;

use App\Http\Controllers\Controller;
use App\Models\Attendance;

class AttendancesPageController extends Controller
{
    public function __invoke()
    {
        $this->authorize('viewAdminAny', Attendance::class);

        return view('admin.import-export.attendances');
    }
}
