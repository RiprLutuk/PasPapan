<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;

class DivisionController extends Controller
{
    public function __invoke()
    {
        return view('admin.master-data.division');
    }
}
