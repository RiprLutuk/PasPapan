<?php

namespace App\Livewire\Admin;

use App\Models\ActivityLog;
use App\Support\ImportExportRunViewService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityLogs extends Component
{
    use WithPagination;

    public $search = '';

    public $dateStart = '';

    public $dateEnd = '';

    public function mount()
    {
        Gate::authorize('viewActivityLogs');

        // Default to this month
        $this->dateStart = now()->startOfMonth()->format('Y-m-d');
        $this->dateEnd = now()->endOfMonth()->format('Y-m-d');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingDateStart(): void
    {
        $this->resetPage();
    }

    public function updatingDateEnd(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $logs = ActivityLog::with('user')
            ->whereHas('user', function ($q) {
                $q->where('group', 'user');
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('action', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%')
                        ->orWhereHas('user', function ($u) {
                            $u->where('name', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->dateStart, function ($query) {
                $query->where('created_at', '>=', \Illuminate\Support\Carbon::parse($this->dateStart)->startOfDay());
            })
            ->when($this->dateEnd, function ($query) {
                $query->where('created_at', '<=', \Illuminate\Support\Carbon::parse($this->dateEnd)->endOfDay());
            })
            ->latest()
            ->paginate(20);

        return view('livewire.admin.activity-logs', [
            'logs' => $logs,
            'recentExportRuns' => app(ImportExportRunViewService::class)
                ->recentForResources(['activity_logs'], auth()->user(), 6),
        ])->layout('layouts.app');
    }

    public function export()
    {
        Gate::authorize('exportActivityLogs');

        return redirect()->route('admin.activity-logs.export', [
            'search' => $this->search,
            'start_date' => $this->dateStart,
            'end_date' => $this->dateEnd,
        ]);
    }
}
