<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\InteractsWithNotificationInbox;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationsPage extends Component
{
    use InteractsWithNotificationInbox;
    use WithPagination;

    public function render()
    {
        return view('livewire.admin.notifications-page', $this->getNotificationInboxViewData())
            ->layout('layouts.app');
    }
}
