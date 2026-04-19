<?php

namespace App\Http\Controllers\Admin\ImportExport;

use App\Http\Controllers\Controller;

class AttendancesPageController extends Controller
{
    public function __invoke()
    {
        return view('admin.import-export.attendances');
    }
}
