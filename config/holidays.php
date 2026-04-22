<?php

return [
    'timeout' => (int) env('HOLIDAY_API_TIMEOUT', 15),

    'sources' => array_values(array_filter(array_map(
        static fn (string $url): string => trim($url),
        explode(',', (string) env(
            'HOLIDAY_API_SOURCES',
            'https://api-hari-libur.vercel.app/api,https://dayoffapi.vercel.app/api'
        ))
    ))),
];
