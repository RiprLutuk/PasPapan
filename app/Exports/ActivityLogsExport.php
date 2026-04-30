<?php

namespace App\Exports;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ActivityLogsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private readonly ?string $search = null,
        private readonly ?string $startDate = null,
        private readonly ?string $endDate = null,
        private readonly string $actorGroup = 'all',
    ) {}

    public function query(): Builder
    {
        return ActivityLog::query()
            ->with('user:id,name')
            ->when(
                in_array($this->actorGroup, ['user', 'admin', 'superadmin'], true),
                fn (Builder $query) => $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('group', $this->actorGroup))
            )
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $nested) {
                    $nested->where('action', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%')
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', '%'.$this->search.'%'));
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
            'Group',
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
            $log->user?->group ?? 'system',
            $log->action,
            $log->description,
            $log->ip_address,
        ];
    }
}
