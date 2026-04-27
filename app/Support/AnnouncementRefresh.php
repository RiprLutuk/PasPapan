<?php

namespace App\Support;

class AnnouncementRefresh
{
    public static function shouldPoll(): bool
    {
        $mode = strtolower((string) config('realtime.announcements.refresh_mode', 'auto'));

        if ($mode === 'poll') {
            return true;
        }

        if (in_array($mode, ['broadcast', 'off'], true)) {
            return false;
        }

        return ! self::broadcastingEnabled();
    }

    public static function broadcastingEnabled(): bool
    {
        $connection = strtolower((string) config('broadcasting.default', 'null'));
        $broadcastConnections = config('realtime.announcements.broadcast_connections', ['reverb', 'pusher', 'ably']);

        return in_array($connection, $broadcastConnections, true);
    }

    public static function pollInterval(): string
    {
        $interval = (string) config('realtime.announcements.poll_interval', '60s');

        return preg_match('/^\d+(ms|s|m)$/', $interval) === 1 ? $interval : '60s';
    }
}
