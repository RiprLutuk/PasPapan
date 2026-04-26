<?php

namespace App\Exports;

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ScheduleRosterExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    private int $rowNumber = 0;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        private readonly User $actor,
        private readonly array $filters = [],
    ) {}

    public function query(): Builder
    {
        return Schedule::query()
            ->with(['user:id,name,nip,division_id,job_title_id', 'user.division:id,name', 'user.jobTitle:id,name', 'shift:id,name,start_time,end_time'])
            ->whereHas('user', fn (Builder $userQuery) => $userQuery->managedBy($this->actor))
            ->when($this->filters['start_date'] ?? null, fn (Builder $query, string $date) => $query->whereDate('date', '>=', $date))
            ->when($this->filters['end_date'] ?? null, fn (Builder $query, string $date) => $query->whereDate('date', '<=', $date))
            ->when($this->filters['division'] ?? null, fn (Builder $query, string|int $division) => $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('division_id', $division)))
            ->when($this->filters['job_title'] ?? null, fn (Builder $query, string|int $jobTitle) => $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('job_title_id', $jobTitle)))
            ->when($this->filters['shift_id'] ?? null, fn (Builder $query, string|int $shiftId) => $query->where('shift_id', $shiftId))
            ->when(($this->filters['off_status'] ?? 'all') !== 'all', fn (Builder $query) => $query->where('is_off', $this->filters['off_status'] === 'off'))
            ->when($this->filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                            $userQuery
                                ->where('name', 'like', '%'.$search.'%')
                                ->orWhere('nip', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('shift', fn (Builder $shiftQuery) => $shiftQuery->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->orderBy('date')
            ->orderBy('user_id')
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            '#',
            'Date',
            'Day',
            'Employee',
            'NIP',
            'Division',
            'Job Title',
            'Shift',
            'Start Time',
            'End Time',
            'Duration',
            'Is Off',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($schedule): array
    {
        return [
            ++$this->rowNumber,
            $schedule->date?->format('Y-m-d'),
            $schedule->date?->translatedFormat('l'),
            $schedule->user?->name,
            (string) ($schedule->user?->nip ?? ''),
            $schedule->user?->division?->name,
            $schedule->user?->jobTitle?->name,
            $schedule->shift?->name,
            $schedule->shift?->formatted_start_time,
            $schedule->shift?->formatted_end_time,
            $schedule->shift?->duration_label,
            $schedule->is_off ? 'Yes' : 'No',
            $schedule->created_at?->format('Y-m-d H:i:s'),
            $schedule->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
