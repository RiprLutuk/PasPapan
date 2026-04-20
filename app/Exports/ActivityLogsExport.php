<?php

namespace App\Exports;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ActivityLogsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        private readonly ?string $search = null,
        private readonly ?string $startDate = null,
        private readonly ?string $endDate = null,
    ) {
    }

    public function query(): Builder
    {
        return ActivityLog::query()
            ->with('user:id,name')
            ->whereHas('user', fn (Builder $query) => $query->where('group', 'user'))
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $nested) {
                    $nested->where('action', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%')
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', '%' . $this->search . '%'));
                });
            })
            ->when($this->startDate, fn (Builder $query) => $query->whereDate('created_at', '>=', $this->startDate))
            ->when($this->endDate, fn (Builder $query) => $query->whereDate('created_at', '<=', $this->endDate))
            ->latest('created_at');
    }

    public function headings(): array
    {
        return [
            'Date',
            'User',
            'Action',
            'Description',
            'IP Address',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($log): array
    {
        return [
            $log->created_at?->format('Y-m-d H:i:s'),
            $log->user?->name ?? 'System',
            $log->action,
            $log->description,
            $log->ip_address,
        ];
    }
}
