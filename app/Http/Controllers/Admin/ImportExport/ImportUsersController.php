<?php

namespace App\Http\Controllers\Admin\ImportExport;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ImportUsersController extends Controller
{
    public function __invoke(Request $request)
    {
        abort(404);
    }
}
