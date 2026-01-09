<?php

namespace App\Livewire;

use App\Models\Announcement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AnnouncementWidget extends Component
{
    public function dismiss($announcementId)
    {
        if (!Auth::check()) {
            return;
        }
        
        // Insert dismissal record
        DB::table('announcement_user_dismissals')->insertOrIgnore([
            'user_id' => Auth::id(),
            'announcement_id' => $announcementId,
            'dismissed_at' => now(),
        ]);
        
        // Refresh the component
        $this->dispatch('$refresh');
    }

    public function render()
    {
        $userId = Auth::id();
        
        $announcements = Announcement::visibleForUser($userId)
            ->orderBy('priority', 'desc')
            ->orderBy('publish_date', 'desc')
            ->take(5)
            ->get();

        return view('livewire.announcement-widget', [
            'announcements' => $announcements,
        ]);
    }
}
