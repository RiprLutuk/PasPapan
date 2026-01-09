<?php

namespace App\Livewire;

use App\Models\Announcement;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationsDropdown extends Component
{
    public function dismiss($announcementId)
    {
        $user = Auth::user();
        $announcement = Announcement::find($announcementId);

        if ($announcement) {
            $announcement->dismissedByUsers()->attach($user->id);
            $this->dispatch('announcement-dismissed'); // Optional: notify frontend
        }
    }

    public function render()
    {
        $user = Auth::user();
        
        $announcements = Announcement::visibleForUser($user->id)
            ->take(5) // Limit to 5 most recent
            ->get();

        return view('livewire.notifications-dropdown', [
            'announcements' => $announcements,
            'unreadCount' => $announcements->count(),
        ]);
    }
}
