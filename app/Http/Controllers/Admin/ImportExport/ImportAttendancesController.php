<?php

namespace App\Http\Controllers\Admin\ImportExport;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;

class ImportAttendancesController extends Controller
{
    public function __invoke(Request $request)
    {
        $this->authorize('viewAny', Attendance::class);

        abort(404);
    }
}
