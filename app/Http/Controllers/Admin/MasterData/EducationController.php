<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;

class EducationController extends Controller
{
    public function __invoke()
    {
        return view('admin.master-data.education');
    }
}
