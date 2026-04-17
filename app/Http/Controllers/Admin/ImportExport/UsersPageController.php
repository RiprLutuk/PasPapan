<?php

namespace App\Http\Controllers\Admin\ImportExport;

use App\Http\Controllers\Controller;

class UsersPageController extends Controller
{
    public function __invoke()
    {
        return view('admin.import-export.users');
    }
}
