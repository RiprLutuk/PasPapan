<?php

namespace App\Livewire;

use App\Models\Announcement;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationsPage extends Component
{
    use WithPagination;

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

    public function render()
    {
        $announcements = Announcement::visibleForUser(Auth::id())
            ->take(10)
            ->get();

        $userNotifications = Auth::user()
            ->notifications()
            ->when($this->showUnreadOnly, fn ($query) => $query->whereNull('read_at'))
            ->latest()
            ->paginate(15);

        return view('livewire.notifications-page', [
            'announcements' => $announcements,
            'notifications' => $userNotifications,
            'unreadCount' => Auth::user()->unreadNotifications()->count() + $announcements->count(),
        ])->layout('layouts.app');
    }
}
