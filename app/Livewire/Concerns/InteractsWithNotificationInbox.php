<?php

namespace App\Livewire\Concerns;

use App\Models\Announcement;
use Illuminate\Support\Facades\Auth;

trait InteractsWithNotificationInbox
{
    public bool $showUnreadOnly = false;
    public string $search = '';
    public string $contentFilter = 'all';

    public function updatingShowUnreadOnly(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedContentFilter(): void
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
        $announcementsQuery = Announcement::visibleForUser($user->id)
            ->when($this->search !== '', function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery
                        ->where('title', 'like', '%' . $this->search . '%')
                        ->orWhere('content', 'like', '%' . $this->search . '%');
                });
            });

        if ($this->contentFilter === 'notifications') {
            $announcementsQuery->whereRaw('1 = 0');
        }

        $announcementCount = (clone $announcementsQuery)->count();

        $notificationsQuery = $user
            ->notifications()
            ->when($this->showUnreadOnly, fn ($query) => $query->whereNull('read_at'))
            ->when($this->contentFilter === 'announcements', fn ($query) => $query->whereRaw('1 = 0'))
            ->when($this->contentFilter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($this->search !== '', function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery
                        ->where('data->title', 'like', '%' . $this->search . '%')
                        ->orWhere('data->message', 'like', '%' . $this->search . '%');
                });
            });

        return [
            'announcements' => (clone $announcementsQuery)
                ->take(10)
                ->get(),
            'notifications' => $notificationsQuery
                ->latest()
                ->paginate(15),
            'notificationCount' => $user->unreadNotifications()->count(),
            'announcementCount' => $announcementCount,
            'unreadCount' => $user->unreadNotifications()->count() + $announcementCount,
        ];
    }
}
