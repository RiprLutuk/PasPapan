<?php

namespace App\Livewire\Admin\Finance;

use App\Helpers\Editions;
use App\Livewire\Finance\Concerns\ManagesCashAdvances;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CashAdvanceManager extends Component
{
    use ManagesCashAdvances;
    use WithPagination;

    private const TABS = ['requests', 'users'];

    #[Url(history: true)]
    public $activeTab = 'requests';

    public $statusFilter = 'pending';

    public $search = '';

    public function boot(): void
    {
        Gate::authorize('manageCashAdvances');
    }

    public function mount()
    {
        if (Editions::payrollLocked()) {
            session()->flash('show-feature-lock', [
                'title' => __('Kasbon Locked'),
                'message' => __('Manage Kasbon is an Enterprise Feature. Please Upgrade.'),
            ]);

            return redirect()->route($this->lockedRedirectRoute());
        }

        $this->normalizeActiveTab();
    }

    public function switchTab($tab)
    {
        if (! in_array($tab, self::TABS, true)) {
            return;
        }

        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updatedActiveTab(): void
    {
        $this->normalizeActiveTab();
        $this->resetPage();
    }

    protected function lockedRedirectRoute(): string
    {
        return 'admin.dashboard';
    }

    public function render()
    {
        return view('livewire.admin.finance.cash-advance-manager', $this->cashAdvanceViewData())
            ->layout('layouts.app');
    }

    private function normalizeActiveTab(): void
    {
        if (! in_array($this->activeTab, self::TABS, true)) {
            $this->activeTab = 'requests';
        }
    }
}
