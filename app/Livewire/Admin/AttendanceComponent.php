<?php

namespace App\Livewire\Admin;

use App\Livewire\Traits\AttendanceDetailTrait;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Component;
use Livewire\WithPagination;

class AttendanceComponent extends Component
{
    use AttendanceDetailTrait;
    use WithPagination, InteractsWithBanner;

    # filter
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
                    $subQ->where('name', 'like', '%' . $this->search . '%')
                         ->orWhere('nip', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->division, fn(Builder $q) => $q->where('division_id', $this->division))
            ->when($this->jobTitle, fn(Builder $q) => $q->where('job_title_id', $this->jobTitle))
            ->with(['division', 'jobTitle'])
            ->paginate(20)->through(function (User $user) use ($start, $end) {
                $cacheKey = "attendance-{$user->id}-{$start->format('Ymd')}-{$end->format('Ymd')}";
                
                $cached = Cache::remember(
                    $cacheKey,
                    now()->addMinutes(5), // Short cache for active admin usage
                    function () use ($user, $start, $end) {
                        /** @var Collection<Attendance>  */
                        // Adjust to fetch range
                        $attendances = Attendance::where('user_id', $user->id)
                            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                            ->get(['id', 'status', 'date', 'latitude_in', 'longitude_in', 'attachment', 'note', 'time_in', 'time_out', 'shift_id']);

                        return $attendances->map(
                            function (Attendance $v) {
                                $v->setAttribute('coordinates', $v->lat_lng);
                                $v->setAttribute('lat', $v->latitude_in);
                                $v->setAttribute('lng', $v->longitude_in);
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
                ) ?? [];
                
                $user->setRelation('attendances', Attendance::hydrate($cached));
                return $user;
            });

        return view('livewire.admin.attendance', ['employees' => $employees, 'dates' => $dates]);
    }
}
