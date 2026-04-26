<?php

namespace App\Http\Controllers\Admin\Attendance;

use App\Helpers\Editions;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessAttendanceReportExportRun;
use App\Models\Attendance;
use App\Models\ImportExportRun;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAdminAny', Attendance::class);

        return view('admin.attendances.index');
    }

    public function report(Request $request)
    {
        $this->authorize('viewAdminAny', Attendance::class);

        if (Editions::reportingLocked()) {
            return to_route('admin.attendances')
                ->with('flash.banner', 'Advanced Reporting is an Enterprise Feature 🔒. Please Upgrade.')
                ->with('flash.bannerStyle', 'danger');
        }

        $validated = $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
            'month' => 'nullable|date_format:Y-m',
            'week' => 'nullable',
            'startDate' => 'nullable|date_format:Y-m-d',
            'endDate' => 'nullable|date_format:Y-m-d',
            'division' => 'nullable|exists:divisions,id',
            'job_title' => 'nullable|exists:job_titles,id',
            'jobTitle' => 'nullable|exists:job_titles,id',
            'format' => 'nullable|in:excel,pdf',
        ]);

        if (! $request->date && ! $request->month && ! $request->week && (! $request->startDate || ! $request->endDate)) {
            return redirect()->back();
        }

        $format = $request->format === 'excel' ? 'excel' : 'pdf';
        $run = ImportExportRun::create([
            'resource' => 'attendance_report',
            'operation' => 'export',
            'status' => 'queued',
            'requested_by_user_id' => $request->user()->id,
            'queue' => 'default',
            'meta' => [
                ...$validated,
                'format' => $format,
            ],
        ]);

        ProcessAttendanceReportExportRun::dispatch($run->id)->onQueue('default');

        return to_route('admin.attendances')
            ->with('flash.banner', __('Attendance :format export queued in background. Track progress from run #:id.', [
                'format' => strtoupper($format === 'excel' ? 'xlsx' : 'pdf'),
                'id' => $run->id,
            ]))
            ->with('flash.bannerStyle', 'success');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Attendance $attendance)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Attendance $attendance)
    {
        //
    }
}
