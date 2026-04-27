<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnnouncementsChanged implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $action = 'changed',
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('announcements');
    }

    public function broadcastAs(): string
    {
        return 'announcements.changed';
    }

    /**
     * @return array<string, string>
     */
    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
        ];
    }
}
