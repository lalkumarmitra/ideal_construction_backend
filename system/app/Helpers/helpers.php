<?php

if (!function_exists('formatIndianNumber')) {
    function formatIndianNumber($number) {
        $decimal = "";
        $numStr = strval($number);

        // Check for decimal part
        if (strpos($numStr, '.') !== false) {
            $parts = explode('.', $numStr);
            $numStr = $parts[0];
            $decimal = '.' . substr($parts[1], 0, 2);  // Limiting to 2 decimal places
        }

        // If the number is less than 1,000, return as-is with the decimal part
        if (strlen($numStr) <= 3) return $numStr . $decimal;

        // Get the last three digits
        $last3 = substr($numStr, -3);
        $rest = substr($numStr, 0, -3);

        // Format the rest with pairs of digits
        $formatted = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
        return ($formatted ? $formatted . ',' : '') . $last3 . $decimal;
    }
}
