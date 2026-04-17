<?php

namespace App\Livewire\User\Finance;

use App\Livewire\Finance\Concerns\ManagesCashAdvances;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TeamCashAdvanceManager extends Component
{
    use ManagesCashAdvances;
    use WithPagination;

    #[Url(history: true)]
    public $activeTab = 'requests';

    public $statusFilter = 'pending';
    public $search = '';

    public function render()
    {
        return view('livewire.user.finance.team-cash-advance-manager', $this->cashAdvanceViewData())
            ->layout('layouts.app');
    }
}
