<?php

namespace App\Livewire;

use App\Models\Announcement;
use App\Models\Holiday;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationsPage extends Component
{
    public function render()
    {
        // Fetch Announcements linked to user or global
        // Assuming Logic from NotificationDropdown or similar
        // For now, let's just get active announcements
        
        // Fetch Announcements linked to user or global
        // Use the 'visible' scope to get currently active announcements
        // We do NOT filter by 'dismissed' here because the Inbox should show all valid notifications
        
        $announcements = Announcement::visible()->get();

        // Also fetch Holidays for context? Or just keep it to "Inbox" messages?
        // User asked for "list kayak inbox pemberitahuan" (list like notification inbox)
        // Usually mixed content is fine, but primarily announcements/system messages.
        
        $announcements = Announcement::visible()->get();
        $userNotifications = Auth::user()->notifications; // Fetch standard Laravel notifications

        return view('livewire.notifications-page', [
            'announcements' => $announcements,
            'notifications' => $userNotifications
        ])->layout('layouts.app');
    }
}
