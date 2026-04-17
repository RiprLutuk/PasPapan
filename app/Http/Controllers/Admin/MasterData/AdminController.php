<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;

class AdminController extends Controller
{
    public function __invoke()
    {
        return view('admin.master-data.admin');
    }
}
