<?php

namespace App\Exports;

use App\Models\Overtime;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OvertimeRequestsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
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
        return Overtime::query()
            ->with(['user:id,name,nip,division_id,job_title_id,hourly_rate', 'user.division:id,name', 'user.jobTitle:id,name', 'approvedBy:id,name'])
            ->whereHas('user', fn (Builder $userQuery) => $userQuery->managedBy($this->actor))
            ->when($this->filters['start_date'] ?? null, fn (Builder $query, string $date) => $query->whereDate('date', '>=', $date))
            ->when($this->filters['end_date'] ?? null, fn (Builder $query, string $date) => $query->whereDate('date', '<=', $date))
            ->when(($this->filters['status'] ?? 'all') !== 'all', fn (Builder $query) => $query->where('status', $this->filters['status']))
            ->when($this->filters['division'] ?? null, fn (Builder $query, string|int $division) => $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('division_id', $division)))
            ->when($this->filters['job_title'] ?? null, fn (Builder $query, string|int $jobTitle) => $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('job_title_id', $jobTitle)))
            ->when($this->filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('reason', 'like', '%'.$search.'%')
                        ->orWhere('rejection_reason', 'like', '%'.$search.'%')
                        ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                            $userQuery
                                ->where('name', 'like', '%'.$search.'%')
                                ->orWhere('nip', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderBy('date')
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            '#',
            'Date',
            'Employee',
            'NIP',
            'Division',
            'Job Title',
            'Start Time',
            'End Time',
            'Duration Minutes',
            'Duration',
            'Status',
            'Reason',
            'Rejection Reason',
            'Reviewed By',
            'Hourly Rate',
            'Estimated Cost',
            'Created At',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($overtime): array
    {
        $durationHours = ((int) $overtime->duration) / 60;
        $hourlyRate = (float) ($overtime->user?->hourly_rate ?? 0);

        return [
            ++$this->rowNumber,
            $overtime->date?->format('Y-m-d'),
            $overtime->user?->name,
            (string) ($overtime->user?->nip ?? ''),
            $overtime->user?->division?->name,
            $overtime->user?->jobTitle?->name,
            $overtime->start_time?->format('H:i:s'),
            $overtime->end_time?->format('H:i:s'),
            $overtime->duration,
            $overtime->duration_text,
            __($overtime->status),
            $overtime->reason,
            $overtime->rejection_reason,
            $overtime->approvedBy?->name,
            $hourlyRate,
            round($durationHours * $hourlyRate, 2),
            $overtime->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
