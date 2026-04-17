<?php

namespace App\Livewire\Shared;

use App\Models\Announcement;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationsDropdown extends Component
{
    protected $listeners = [
        'refresh-notifications' => '$refresh',
        'announcement-dismissed' => '$refresh'
    ];

    public function dismiss($announcementId)
    {
        $user = Auth::user();
        $announcement = Announcement::find($announcementId);

        if ($announcement) {
            $announcement->dismissedByUsers()->attach($user->id);
            $this->dispatch('announcement-dismissed'); 
        }
    }

    public function markAsRead($notificationId)
    {
        $notification = Auth::user()->notifications()->find($notificationId);

        if ($notification) {
            $notification->markAsRead();
        }
    }

    public function render()
    {
        $user = Auth::user();
        
        $announcements = Announcement::visibleForUser($user->id)
            ->take(5)
            ->get();

        $notifications = $user->unreadNotifications()->take(5)->get();
        $totalUnread = $notifications->count() + $announcements->count();

        return view('livewire.shared.notifications-dropdown', [
            'announcements' => $announcements,
            'notifications' => $notifications,
            'unreadCount' => $totalUnread,
        ]);
    }
}
