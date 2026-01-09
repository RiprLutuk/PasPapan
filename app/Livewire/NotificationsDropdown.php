<?php

namespace App\Livewire;

use Livewire\Component;

use App\Models\Announcement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationsDropdown extends Component
{
    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        
        if (isset($notification->data['url'])) {
            return redirect($notification->data['url']);
        }
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
    }

    public function dismissAnnouncement($announcementId)
    {
        if (!Auth::check()) {
            return;
        }
        
        DB::table('announcement_user_dismissals')->insertOrIgnore([
            'user_id' => Auth::id(),
            'announcement_id' => $announcementId,
            'dismissed_at' => now(),
        ]);
    }

    public function render()
    {
        $userId = Auth::id();
        
        $announcements = Announcement::visibleForUser($userId)
            ->orderBy('priority', 'desc')
            ->orderBy('publish_date', 'desc')
            ->take(5)
            ->get();

        return view('livewire.notifications-dropdown', [
            'notifications' => Auth::user()->unreadNotifications,
            'announcements' => $announcements,
        ]);
    }
}
