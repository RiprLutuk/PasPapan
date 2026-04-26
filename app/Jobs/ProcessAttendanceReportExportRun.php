<?php

namespace App\Jobs;

use App\Exports\AttendanceExport;
use App\Models\Attendance;
use App\Models\ImportExportRun;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessAttendanceReportExportRun implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $runId) {}

    public function handle(): void
    {
        $run = ImportExportRun::query()->findOrFail($this->runId);
        $meta = $run->meta ?? [];
        $format = ($meta['format'] ?? 'pdf') === 'excel' ? 'excel' : 'pdf';

        [$data, $rowCount] = $this->reportData($run, $meta);
        $extension = $format === 'excel' ? 'xlsx' : 'pdf';
        $fileName = 'attendance-report-'.now()->format('Ymd-His').'.'.$extension;
        $filePath = 'import-export/exports/'.now()->format('Y/m/d').'/'.$fileName;

        $run->markRunning([
            'progress_percentage' => $rowCount > 0 ? 20 : 80,
            'total_rows' => $rowCount,
        ]);

        if ($format === 'excel') {
            $data['isExcel'] = true;
            Excel::store(new AttendanceExport($data), $filePath, 'local');
            $mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        } else {
            $pdf = Pdf::loadView('admin.attendances.report', $data)->setPaper('a3', 'landscape');
            Storage::disk('local')->put($filePath, $pdf->output());
            $mimeType = 'application/pdf';
        }

        $run->markCompleted([
            'processed_rows' => $rowCount,
            'file_disk' => 'local',
            'file_path' => $filePath,
            'file_name' => $fileName,
            'mime_type' => $mimeType,
            'size_bytes' => Storage::disk('local')->size($filePath),
        ]);
    }

    private function reportData(ImportExportRun $run, array $meta): array
    {
        $requester = $run->requestedBy ?: User::query()->where('group', 'superadmin')->first();
        $carbon = new Carbon;
        $start = null;
        $end = null;

        if (! empty($meta['date'])) {
            $dates = [$carbon->parse($meta['date'])->settings(['formatFunction' => 'translatedFormat'])];
            $rangeKey = $meta['date'];
            $queryStart = $meta['date'];
            $queryEnd = $meta['date'];
        } elseif (! empty($meta['week'])) {
            $start = $carbon->parse($meta['week'])->settings(['formatFunction' => 'translatedFormat'])->startOfWeek();
            $end = $carbon->parse($meta['week'])->settings(['formatFunction' => 'translatedFormat'])->endOfWeek();
            $dates = $start->range($end)->toArray();
            $rangeKey = $meta['week'];
            $queryStart = Carbon::parse($meta['week'])->startOfWeek()->toDateString();
            $queryEnd = Carbon::parse($meta['week'])->endOfWeek()->toDateString();
        } elseif (! empty($meta['month'])) {
            $start = $carbon->parse($meta['month'])->settings(['formatFunction' => 'translatedFormat'])->startOfMonth();
            $end = $carbon->parse($meta['month'])->settings(['formatFunction' => 'translatedFormat'])->endOfMonth();
            $dates = $start->range($end)->toArray();
            $rangeKey = $meta['month'];
            $queryStart = Carbon::parse($meta['month'])->startOfMonth()->toDateString();
            $queryEnd = Carbon::parse($meta['month'])->endOfMonth()->toDateString();
        } else {
            $start = $carbon->parse($meta['startDate'])->settings(['formatFunction' => 'translatedFormat']);
            $end = $carbon->parse($meta['endDate'])->settings(['formatFunction' => 'translatedFormat']);
            $dates = $start->range($end)->toArray();
            $rangeKey = $meta['startDate'].':'.$meta['endDate'];
            $queryStart = $meta['startDate'];
            $queryEnd = $meta['endDate'];
        }

        $jobTitleFilter = $meta['jobTitle'] ?? $meta['job_title'] ?? null;

        $employees = User::where('group', 'user')
            ->when($requester, fn (Builder $query) => $query->managedBy($requester))
            ->when($meta['division'] ?? null, fn (Builder $query) => $query->where('division_id', $meta['division']))
            ->when($jobTitleFilter, fn (Builder $query) => $query->where('job_title_id', $jobTitleFilter))
            ->with(['division', 'jobTitle'])
            ->orderBy('name')
            ->get();

        $attendancesByUser = $employees->isEmpty()
            ? collect()
            : Attendance::query()
                ->with('shift:id,name')
                ->whereIn('user_id', $employees->pluck('id'))
                ->whereBetween('date', [$queryStart, $queryEnd])
                ->get(['id', 'user_id', 'status', 'date', 'latitude_in', 'longitude_in', 'attachment', 'note', 'time_in', 'time_out', 'shift_id'])
                ->map(fn (Attendance $attendance) => $this->decorateAttendanceForReport($attendance))
                ->groupBy('user_id');

        $employees->transform(function (User $user) use ($attendancesByUser) {
            $user->setRelation('attendances', new EloquentCollection($attendancesByUser->get($user->id, collect())->all()));

            return $user;
        });

        return [[
            'employees' => $employees,
            'dates' => $dates ?? [],
            'date' => $meta['date'] ?? null,
            'month' => $meta['month'] ?? null,
            'week' => $meta['week'] ?? null,
            'division' => $meta['division'] ?? null,
            'jobTitle' => $jobTitleFilter,
            'start' => $start,
            'end' => $end,
            'rangeKey' => $rangeKey,
        ], $employees->count()];
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
}
