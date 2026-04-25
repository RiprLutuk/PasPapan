<?php

namespace App\Http\Controllers\Admin\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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

        $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
            'month' => 'nullable|date_format:Y-m',
            'week' => 'nullable',
            'startDate' => 'nullable|date_format:Y-m-d',
            'endDate' => 'nullable|date_format:Y-m-d',
            'division' => 'nullable|exists:divisions,id',
            'job_title' => 'nullable|exists:job_titles,id',
            'jobTitle' => 'nullable|exists:job_titles,id',
        ]);

        if (! $request->date && ! $request->month && ! $request->week && (! $request->startDate || ! $request->endDate)) {
            return redirect()->back();
        }

        $carbon = new Carbon;
        $start = null;
        $end = null;

        if ($request->date) {
            $dates = [$carbon->parse($request->date)->settings(['formatFunction' => 'translatedFormat'])];
        } elseif ($request->week) {
            $start = $carbon->parse($request->week)->settings(['formatFunction' => 'translatedFormat'])->startOfWeek();
            $end = $carbon->parse($request->week)->settings(['formatFunction' => 'translatedFormat'])->endOfWeek();
            $dates = $start->range($end)->toArray();
        } elseif ($request->month) {
            $start = $carbon->parse($request->month)->settings(['formatFunction' => 'translatedFormat'])->startOfMonth();
            $end = $carbon->parse($request->month)->settings(['formatFunction' => 'translatedFormat'])->endOfMonth();
            $dates = $start->range($end)->toArray();
        } elseif ($request->startDate && $request->endDate) {
            $start = $carbon->parse($request->startDate)->settings(['formatFunction' => 'translatedFormat']);
            $end = $carbon->parse($request->endDate)->settings(['formatFunction' => 'translatedFormat']);
            $dates = $start->range($end)->toArray();
        }

        if ($request->date) {
            $rangeKey = $request->date;
            $qStart = $request->date;
            $qEnd = $request->date;
        } elseif ($request->week) {
            $rangeKey = $request->week;
            $qStart = Carbon::parse($request->week)->startOfWeek()->toDateString();
            $qEnd = Carbon::parse($request->week)->endOfWeek()->toDateString();
        } elseif ($request->month) {
            $rangeKey = $request->month;
            $qStart = Carbon::parse($request->month)->startOfMonth()->toDateString();
            $qEnd = Carbon::parse($request->month)->endOfMonth()->toDateString();
        } else {
            $rangeKey = $request->startDate.':'.$request->endDate;
            $qStart = $request->startDate;
            $qEnd = $request->endDate;
        }

        $jobTitleFilter = $request->jobTitle ?? $request->job_title;

        $employees = User::where('group', 'user')
            ->managedBy(auth()->user())
            ->when($request->division, fn (Builder $q) => $q->where('division_id', $request->division))
            ->when($jobTitleFilter, fn (Builder $q) => $q->where('job_title_id', $jobTitleFilter))
            ->with(['division', 'jobTitle'])
            ->orderBy('name')
            ->get();

        $attendancesByUser = $employees->isEmpty()
            ? collect()
            : Attendance::query()
                ->with('shift:id,name')
                ->whereIn('user_id', $employees->pluck('id'))
                ->whereBetween('date', [$qStart, $qEnd])
                ->get(['id', 'user_id', 'status', 'date', 'latitude_in', 'longitude_in', 'attachment', 'note', 'time_in', 'time_out', 'shift_id'])
                ->map(fn (Attendance $attendance) => $this->decorateAttendanceForReport($attendance))
                ->groupBy('user_id');

        $employees->transform(function (User $user) use ($attendancesByUser) {
            $user->setRelation('attendances', new EloquentCollection($attendancesByUser->get($user->id, collect())->all()));

            return $user;
        });

        $data = [
            'employees' => $employees,
            'dates' => $dates ?? [],
            'date' => $request->date,
            'month' => $request->month,
            'week' => $request->week,
            'division' => $request->division,
            'jobTitle' => $jobTitleFilter,
            'start' => $start,
            'end' => $end,
            'rangeKey' => $rangeKey,
        ];

        if ($request->format === 'excel') {
            $data['isExcel'] = true;

            return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\AttendanceExport($data), 'attendance_report.xlsx');
        }

        $pdf = Pdf::loadView('admin.attendances.report', $data)->setPaper('a3', 'landscape');

        return $pdf->stream();
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Attendance $attendance)
    {
        //
    }

    private function decorateAttendanceForReport(Attendance $attendance): Attendance
    {
        $attendance->setAttribute('coordinates', $attendance->lat_lng);
        $attendance->setAttribute('lat', $attendance->latitude_in);
        $attendance->setAttribute('lng', $attendance->longitude_in);

        if ($attendance->attachment) {
            $attendance->setAttribute('attachment', $attendance->attachment_url);
        }

        if ($attendance->shift) {
            $attendance->setAttribute('shift', $attendance->shift->name);
        }

        return $attendance;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Attendance $attendance)
    {
        //
    }
}
