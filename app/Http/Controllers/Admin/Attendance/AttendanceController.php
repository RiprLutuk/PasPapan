<?php

namespace App\Http\Controllers\Admin\Attendance;

use App\Models\Attendance;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Attendance::class);

        return view('admin.attendances.index');
    }

    public function report(Request $request)
    {
        $this->authorize('viewAny', Attendance::class);

        $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
            'month' => 'nullable|date_format:Y-m',
            'week' => 'nullable',
            'startDate' => 'nullable|date_format:Y-m-d',
            'endDate' => 'nullable|date_format:Y-m-d',
            'division' => 'nullable|exists:divisions,id',
            'job_title' => 'nullable|exists:job_titles,id',
        ]);

        if (!$request->date && !$request->month && !$request->week && (!$request->startDate || !$request->endDate)) {
            return redirect()->back();
        }

        $carbon = new Carbon;
        $start = null;
        $end = null;

        if ($request->date) {
            $dates = [$carbon->parse($request->date)->settings(['formatFunction' => 'translatedFormat'])];
        } else if ($request->week) {
            $start = $carbon->parse($request->week)->settings(['formatFunction' => 'translatedFormat'])->startOfWeek();
            $end = $carbon->parse($request->week)->settings(['formatFunction' => 'translatedFormat'])->endOfWeek();
            $dates = $start->range($end)->toArray();
        } else if ($request->month) {
            $start = $carbon->parse($request->month)->settings(['formatFunction' => 'translatedFormat'])->startOfMonth();
            $end = $carbon->parse($request->month)->settings(['formatFunction' => 'translatedFormat'])->endOfMonth();
            $dates = $start->range($end)->toArray();
        } else if ($request->startDate && $request->endDate) {
            $start = $carbon->parse($request->startDate)->settings(['formatFunction' => 'translatedFormat']);
            $end = $carbon->parse($request->endDate)->settings(['formatFunction' => 'translatedFormat']);
            $dates = $start->range($end)->toArray();
        }

        $employees = User::where('group', 'user')
            ->managedBy(auth()->user())
            ->when($request->division, fn (Builder $q) => $q->where('division_id', $request->division))
            ->when($request->jobTitle, fn (Builder $q) => $q->where('job_title_id', $request->jobTitle))
            ->get()
            ->map(function ($user) use ($request, $dates) {
                // Determine Range for Cache Key
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
                   $rangeKey = $request->startDate . ':' . $request->endDate;
                   $qStart = $request->startDate;
                   $qEnd = $request->endDate;
                }

                $attendances = new Collection(Cache::remember(
                    "attendance-$user->id-$rangeKey",
                    now()->addMinutes(5),
                    function () use ($user, $qStart, $qEnd) {
                        /** @var Collection<Attendance>  */
                        $attendances = Attendance::where('user_id', $user->id)
                            ->whereBetween('date', [$qStart, $qEnd])
                            ->get();

                        return $attendances->map(
                            function (Attendance $v) {
                                $v->setAttribute('coordinates', $v->lat_lng);
                                $v->setAttribute('lat', $v->latitude);
                                $v->setAttribute('lng', $v->longitude);
                                if ($v->attachment) {
                                    $v->setAttribute('attachment', $v->attachment_url);
                                }
                                if ($v->shift) {
                                    $v->setAttribute('shift', $v->shift->name);
                                }
                                return $v->getAttributes();
                            }
                        )->toArray();
                    }
                ) ?? []);
                
                $user->attendances = $attendances;
                return $user;
            });

        $data = [
            'employees' => $employees,
            'dates' => $dates ?? [],
            'date' => $request->date,
            'month' => $request->month,
            'week' => $request->week,
            'division' => $request->division,
            'jobTitle' => $request->jobTitle,
            'start' => $start,
            'end' => $end
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

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Attendance $attendance)
    {
        //
    }
}
