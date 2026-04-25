<?php

namespace App\Livewire\Admin\Finance;

use App\Livewire\Finance\Concerns\ManagesCashAdvances;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CashAdvanceManager extends Component
{
    use ManagesCashAdvances;
    use WithPagination;

    #[Url(history: true)]
    public $activeTab = 'requests';

    public $statusFilter = 'pending';

    public $search = '';

    public function boot(): void
    {
        Gate::authorize('manageCashAdvances');
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
}
