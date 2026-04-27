<?php

return [

    'announcements' => [
        'refresh_mode' => env('ANNOUNCEMENT_REFRESH_MODE', 'auto'),
        'poll_interval' => env('ANNOUNCEMENT_POLL_INTERVAL', '60s'),
        'broadcast_connections' => ['reverb', 'pusher', 'ably'],
    ],

];
