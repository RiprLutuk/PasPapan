<?php

namespace App;

class Helpers
{
    public static function getGoogleMapsUrl($lat, $lng)
    {
        return "https://maps.google.com/maps?q=$lat,$lng";
    }

    /**
     * Get the URL path from the app URL
     *
     * E.g. base url/app url = http://localhost:8000/path => path
     *
     * Returns empty string if base url is root path
     */
    public static function getNonRootBaseUrlPath()
    {
        $segments = explode('/', parse_url(config('app.url'), PHP_URL_PATH));
        return count($segments) < 2 ? '' : $segments[1];
    }

    /**
     * Format time based on application settings
     *
     * @param string|\Carbon\Carbon|null $time
     * @return string
     */
    public static function format_time($time)
    {
        if (!$time) return '-';

        if (is_string($time)) {
             try {
                 $time = \Carbon\Carbon::parse($time);
             } catch (\Exception $e) {
                 return $time;
             }
        }

        $format = \App\Models\Setting::getValue('app.time_format', '24');
        $showSeconds = (bool) \App\Models\Setting::getValue('app.show_seconds', false);

        if ($format == '12') {
            $formatString = $showSeconds ? 'h:i:s A' : 'h:i A';
        } else {
            $formatString = $showSeconds ? 'H:i:s' : 'H:i';
        }

        return $time->format($formatString);
    }
}
