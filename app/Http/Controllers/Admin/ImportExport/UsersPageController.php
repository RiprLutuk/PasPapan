<?php

namespace App\Http\Controllers\Admin\ImportExport;

use App\Http\Controllers\Controller;

class UsersPageController extends Controller
{
    public function __invoke()
    {
        $this->authorize('viewUserImportExport');

        return view('admin.import-export.users');
    }
}
