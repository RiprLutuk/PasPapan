<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\InteractsWithNotificationInbox;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationsPage extends Component
{
    use InteractsWithNotificationInbox;
    use WithPagination;

    public function boot(): void
    {
        Gate::authorize('manageAdminNotifications');
    }

    public function render()
    {
        return view('livewire.admin.notifications-page', $this->getNotificationInboxViewData())
            ->layout('layouts.app');
    }
}
