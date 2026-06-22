<?php

namespace App\Livewire\User\Finance;

use App\Helpers\Editions;
use App\Livewire\Finance\Concerns\ManagesCashAdvances;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TeamCashAdvanceManager extends Component
{
    use ManagesCashAdvances;
    use WithPagination;

    private const TABS = ['requests', 'users'];

    #[Url(history: true)]
    public $activeTab = 'requests';

    public $statusFilter = 'pending';

    public $search = '';

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

    public function setStatusFilter(string $status): void
    {
        if (! in_array($status, ['all', 'pending', 'pending_finance', 'approved', 'paid', 'rejected'], true)) {
            return;
        }

        $this->statusFilter = $status;
        $this->resetPage();
    }

    public function updatedActiveTab(): void
    {
        $this->normalizeActiveTab();
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.user.finance.team-cash-advance-manager', $this->cashAdvanceViewData())
            ->layout('layouts.app');
    }

    private function normalizeActiveTab(): void
    {
        if (! in_array($this->activeTab, self::TABS, true)) {
            $this->activeTab = 'requests';
        }
    }
}
