<?php

// Test whitespace normalization and matching

function normalizeWhitespace($text)
{
    if (empty($text)) {
        return $text;
    }
    
    // Replace multiple spaces with single space
    $text = preg_replace('/\s+/', ' ', $text);
    
    // Trim leading/trailing spaces
    return trim($text);
}

// Test cases
$testCases = [
    // [code, filename, should_match]
    ['DMH 470', 'DMH 470.jpg', true],
    ['DMH 470', 'DMH 470_80x80cm.jpg', true],
    ['DMH 470 450      123   456', 'DMH 470 450      123   456.jpg', true],
    ['DMH 470 450      123   456', 'DMH 470 450 123 456.jpg', true],
    ['DMH 470 450      123   456', 'DMH 470 450 123 456_resize.jpg', true],
    ['DMH  470', 'DMH 470.jpg', true],
    ['DMH   470   450', 'DMH 470 450.jpg', true],
    ['DMH 470', 'DMH 471.jpg', false],
];

echo "Testing whitespace normalization and matching:\n\n";
echo str_repeat('=', 100) . "\n";

foreach ($testCases as $index => $test) {
    list($code, $filename, $shouldMatch) = $test;
    
    // Remove extension for comparison
    $filenameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
    
    // Normalize both
    $normalizedCode = normalizeWhitespace($code);
    $normalizedFilename = normalizeWhitespace($filenameWithoutExt);
    
    // Check if matches
    $matches = stripos($normalizedFilename, $normalizedCode) === 0;
    
    // Result
    $result = $matches === $shouldMatch ? '✅ PASS' : '❌ FAIL';
    
    echo sprintf(
        "Test %d: %s\n  Code: '%s' (normalized: '%s')\n  File: '%s' (normalized: '%s')\n  Match: %s (expected: %s)\n\n",
        $index + 1,
        $result,
        $code,
        $normalizedCode,
        $filenameWithoutExt,
        $normalizedFilename,
        $matches ? 'YES' : 'NO',
        $shouldMatch ? 'YES' : 'NO'
    );
}

echo str_repeat('=', 100) . "\n";
