<?php

namespace App\Livewire\Admin;

use App\Livewire\Traits\AttendanceDetailTrait;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Component;
use Livewire\WithPagination;

class AttendanceComponent extends Component
{
    use AttendanceDetailTrait;
    use InteractsWithBanner, WithPagination;

    // filter
    public $startDate;

    public $endDate;

    public ?string $division = null;

    public ?string $jobTitle = null;

    public ?string $search = null;

    public function mount()
    {
        $this->startDate = now()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function updating($key): void
    {
        if ($key === 'search' || $key === 'division' || $key === 'jobTitle' || $key === 'startDate' || $key === 'endDate') {
            $this->resetPage();
        }
    }

    public function render()
    {
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);

        // Validation: Prevent inverted range
        if ($start->gt($end)) {
            $temp = $start;
            $start = $end;
            $end = $temp;
        }

        $dates = $start->range($end)->toArray();

        $employees = User::where('group', 'user')
            ->managedBy(auth()->user())
            ->when($this->search, function (Builder $q) {
                return $q->where(function ($subQ) {
                    $subQ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('nip', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->division, fn (Builder $q) => $q->where('division_id', $this->division))
            ->when($this->jobTitle, fn (Builder $q) => $q->where('job_title_id', $this->jobTitle))
            ->with(['division', 'jobTitle'])
            ->orderBy('name')
            ->paginate(20);

        $userIds = $employees->getCollection()->pluck('id');
        $attendancesByUser = $userIds->isEmpty()
            ? collect()
            : Attendance::query()
                ->with('shift:id,name')
                ->whereIn('user_id', $userIds)
                ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                ->get(['id', 'user_id', 'status', 'date', 'latitude_in', 'longitude_in', 'attachment', 'note', 'time_in', 'time_out', 'shift_id'])
                ->map(fn (Attendance $attendance) => $this->decorateAttendanceForGrid($attendance))
                ->groupBy('user_id');

        $employees->getCollection()->transform(function (User $user) use ($attendancesByUser) {
            $user->setRelation('attendances', new EloquentCollection($attendancesByUser->get($user->id, collect())->all()));

            return $user;
        });

        return view('livewire.admin.attendance', ['employees' => $employees, 'dates' => $dates]);
    }

    private function decorateAttendanceForGrid(Attendance $attendance): Attendance
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
