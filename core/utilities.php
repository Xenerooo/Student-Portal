<?php

function getOrdinal($number) {
    if (!is_numeric($number)) {
        return $number; // Return as is if not a number
    }
    
    // Handle 11, 12, and 13 specially as they all use 'th'
    if (in_array(($number % 100), array(11, 12, 13))) {
        return $number . 'th';
    }

    // Determine the suffix based on the last digit
    switch ($number % 10) {
        case 1:
            return $number . 'st';
        case 2:
            return $number . 'nd';
        case 3:
            return $number . 'rd';
        default:
            return $number . 'th';
    }
}

// ... your existing code ...

?>