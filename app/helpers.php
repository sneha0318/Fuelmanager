<?php

if (!function_exists('localDateTime')) {
    /**
     * Convert a Carbon datetime to local timezone and format it
     *
     * @param \Carbon\Carbon|string $datetime
     * @param string $format
     * @return string
     */
    function localDateTime($datetime, $format = 'Y-m-d H:i:s')
    {
        if (is_string($datetime)) {
            $datetime = \Carbon\Carbon::parse($datetime);
        }
        
        return $datetime->timezone(config('app.timezone', 'Asia/Kolkata'))->format($format);
    }
}

if (!function_exists('localDate')) {
    /**
     * Convert a Carbon datetime to local timezone and format as date
     *
     * @param \Carbon\Carbon|string $datetime
     * @param string $format
     * @return string
     */
    function localDate($datetime, $format = 'Y-m-d')
    {
        return localDateTime($datetime, $format);
    }
}

if (!function_exists('localTime')) {
    /**
     * Convert a Carbon datetime to local timezone and format as time
     *
     * @param \Carbon\Carbon|string $datetime
     * @param string $format
     * @return string
     */
    function localTime($datetime, $format = 'H:i')
    {
        return localDateTime($datetime, $format);
    }
}
