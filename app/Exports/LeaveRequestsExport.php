<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LeaveRequestsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
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
        return Attendance::query()
            ->with(['user:id,name,nip,division_id,job_title_id', 'user.division:id,name', 'user.jobTitle:id,name', 'leaveType:id,name', 'approvedBy:id,name'])
            ->whereIn('status', Attendance::REQUEST_STATUSES)
            ->managedBy($this->actor)
            ->when($this->filters['start_date'] ?? null, fn (Builder $query, string $date) => $query->whereDate('date', '>=', $date))
            ->when($this->filters['end_date'] ?? null, fn (Builder $query, string $date) => $query->whereDate('date', '<=', $date))
            ->when(($this->filters['approval_status'] ?? 'all') !== 'all', fn (Builder $query) => $query->where('approval_status', $this->filters['approval_status']))
            ->when(($this->filters['request_type'] ?? 'all') !== 'all', function (Builder $query): void {
                $requestType = (string) $this->filters['request_type'];

                if (ctype_digit($requestType)) {
                    $query->where('leave_type_id', (int) $requestType);

                    return;
                }

                $query->where('status', $requestType);
            })
            ->when($this->filters['division'] ?? null, fn (Builder $query, string|int $division) => $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('division_id', $division)))
            ->when($this->filters['job_title'] ?? null, fn (Builder $query, string|int $jobTitle) => $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('job_title_id', $jobTitle)))
            ->when($this->filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('note', 'like', '%'.$search.'%')
                        ->orWhere('rejection_note', 'like', '%'.$search.'%')
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
            'Request Type',
            'Approval Status',
            'Note',
            'Rejection Note',
            'Reviewed By',
            'Reviewed At',
            'Created At',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($leave): array
    {
        return [
            ++$this->rowNumber,
            $leave->date?->format('Y-m-d'),
            $leave->user?->name,
            (string) ($leave->user?->nip ?? ''),
            $leave->user?->division?->name,
            $leave->user?->jobTitle?->name,
            $leave->leaveType?->name ?? __($leave->status),
            __($leave->approval_status),
            $leave->note,
            $leave->rejection_note,
            $leave->approvedBy?->name,
            $leave->approved_at ? Carbon::parse($leave->approved_at)->format('Y-m-d H:i:s') : null,
            $leave->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
