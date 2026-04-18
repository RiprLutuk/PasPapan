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
            $announcement->dismissedByUsers()->syncWithoutDetaching([$user->id]);
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

        $announcementsQuery = Announcement::visibleForUser($user->id);
        $notificationsQuery = $user->unreadNotifications();

        $announcements = (clone $announcementsQuery)
            ->latest()
            ->take(5)
            ->get();

        $notifications = (clone $notificationsQuery)
            ->latest()
            ->take(5)
            ->get();

        $totalUnread = $notificationsQuery->count() + $announcementsQuery->count();
        $items = $notifications
            ->map(fn ($notification) => [
                'type' => 'notification',
                'created_at' => $notification->created_at,
                'data' => $notification,
            ])
            ->concat(
                $announcements->map(fn ($announcement) => [
                    'type' => 'announcement',
                    'created_at' => $announcement->created_at,
                    'data' => $announcement,
                ]),
            )
            ->sortByDesc('created_at')
            ->take(5)
            ->values();

        return view('livewire.shared.notifications-dropdown', [
            'announcements' => $announcements,
            'notifications' => $notifications,
            'items' => $items,
            'unreadCount' => $totalUnread,
        ]);
    }
}
