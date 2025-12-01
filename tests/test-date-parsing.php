<?php

// Test date parsing

$dates = [
    '02.01.2013',
    '23.04.2012',
    '28.03.2016',
    '01/05/2020',
    '15-06-2021',
    '2024-12-01',
    44927, // Excel serial date
];

function parseDate($date)
{
    try {
        if (empty($date)) {
            return date('Y-m-d');
        }
        
        // If it's a numeric value, it's likely an Excel serial date
        if (is_numeric($date)) {
            // For testing without PhpSpreadsheet
            $baseDate = new DateTime('1899-12-30');
            $baseDate->modify("+{$date} days");
            return $baseDate->format('Y-m-d');
        }
        
        // Convert to string and trim
        $date = trim((string)$date);
        
        // Try to parse various date formats
        // Format: dd.mm.yyyy (European format with dots)
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $date, $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = $matches[3];
            return "{$year}-{$month}-{$day}";
        }
        
        // Format: dd/mm/yyyy
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date, $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = $matches[3];
            return "{$year}-{$month}-{$day}";
        }
        
        // Format: dd-mm-yyyy
        if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $date, $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = $matches[3];
            return "{$year}-{$month}-{$day}";
        }
        
        // Try strtotime for other formats (yyyy-mm-dd, etc.)
        $timestamp = strtotime($date);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }
        
        // If all else fails, return current date
        return date('Y-m-d');
        
    } catch (Exception $e) {
        return date('Y-m-d');
    }
}

echo "Testing date parsing:\n\n";
foreach ($dates as $date) {
    $parsed = parseDate($date);
    echo "Input: " . str_pad($date, 15) . " => Output: $parsed\n";
}
