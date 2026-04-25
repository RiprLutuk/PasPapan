<?php

namespace App\Livewire\Shared;

use App\Models\Announcement;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class HighPriorityAnnouncementModal extends Component
{
    public bool $showModal = false;

    public ?int $activeAnnouncementId = null;

    public function mount(): void
    {
        $this->syncAnnouncementState();
    }

    public function dismiss(): void
    {
        $announcement = $this->currentAnnouncement();

        if ($announcement) {
            if (($announcement->modal_behavior ?? 'acknowledge') === 'acknowledge') {
                $announcement->dismissedByUsers()->syncWithoutDetaching([
                    Auth::id() => ['dismissed_at' => now()],
                ]);
            }

            $this->dispatch('announcement-dismissed');
        }

        $this->showModal = false;
        $this->activeAnnouncementId = null;
        $this->syncAnnouncementState();
    }

    public function syncAnnouncementState(): void
    {
        $announcement = $this->currentAnnouncement();

        if ($announcement && ($announcement->modal_behavior ?? 'acknowledge') === 'once') {
            $announcement->viewedByUsers()->syncWithoutDetaching([
                Auth::id() => ['seen_at' => now()],
            ]);
        }

        $this->activeAnnouncementId = $announcement?->id;
        $this->showModal = $announcement !== null;
    }

    protected function currentAnnouncement(): ?Announcement
    {
        $userId = Auth::id();

        if (! $userId) {
            return null;
        }

        if ($this->activeAnnouncementId) {
            $current = Announcement::visible()
                ->where('priority', 'high')
                ->whereKey($this->activeAnnouncementId)
                ->first();

            if ($current && $this->showModal) {
                return $current;
            }
        }

        return Announcement::visible()
            ->where('priority', 'high')
            ->where(function ($query) use ($userId) {
                $query
                    ->where(function ($modeQuery) use ($userId) {
                        $modeQuery->where('modal_behavior', 'acknowledge')
                            ->whereDoesntHave('dismissedByUsers', function ($dismissedQuery) use ($userId) {
                                $dismissedQuery->where('user_id', $userId);
                            });
                    })
                    ->orWhere(function ($modeQuery) use ($userId) {
                        $modeQuery->where('modal_behavior', 'once')
                            ->whereDoesntHave('viewedByUsers', function ($viewedQuery) use ($userId) {
                                $viewedQuery->where('user_id', $userId);
                            });
                    });
            })
            ->orderByDesc('publish_date')
            ->orderByDesc('created_at')
            ->first();
    }

    public function render()
    {
        return view('livewire.shared.high-priority-announcement-modal', [
            'announcement' => $this->currentAnnouncement(),
        ]);
    }
}
