<?php

namespace App\Http\Controllers\Admin\Employees;

use App\Http\Controllers\Controller;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.employees.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id) {}
}
