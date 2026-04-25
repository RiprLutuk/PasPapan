<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::redirect('/offline', '/offline.html')->name('offline');

require __DIR__.'/web/system.php';
require __DIR__.'/web/files.php';
require __DIR__.'/web/user.php';
require __DIR__.'/web/payroll.php';
require __DIR__.'/web/admin.php';
