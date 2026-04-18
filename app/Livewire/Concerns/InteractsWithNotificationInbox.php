<?php

namespace App\Livewire\Concerns;

use App\Models\Announcement;
use Illuminate\Support\Facades\Auth;

trait InteractsWithNotificationInbox
{
    public bool $showUnreadOnly = false;

    public function updatingShowUnreadOnly(): void
    {
        $this->resetPage();
    }

    public function markAsRead(string $notificationId): void
    {
        $notification = Auth::user()->notifications()->find($notificationId);

        if ($notification && is_null($notification->read_at)) {
            $notification->markAsRead();
        }
    }

    public function markAllAsRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();
    }

    public function dismissAnnouncement(int $announcementId): void
    {
        $announcement = Announcement::visibleForUser(Auth::id())
            ->whereKey($announcementId)
            ->first();

        if ($announcement) {
            $announcement->dismissedByUsers()->syncWithoutDetaching([
                Auth::id() => ['dismissed_at' => now()],
            ]);
        }
    }

    protected function getNotificationInboxViewData(): array
    {
        $user = Auth::user();
        $announcementsQuery = Announcement::visibleForUser($user->id);
        $announcementCount = (clone $announcementsQuery)->count();

        return [
            'announcements' => (clone $announcementsQuery)
                ->take(10)
                ->get(),
            'notifications' => $user
                ->notifications()
                ->when($this->showUnreadOnly, fn ($query) => $query->whereNull('read_at'))
                ->latest()
                ->paginate(15),
            'notificationCount' => $user->unreadNotifications()->count(),
            'announcementCount' => $announcementCount,
            'unreadCount' => $user->unreadNotifications()->count() + $announcementCount,
        ];
    }
}
