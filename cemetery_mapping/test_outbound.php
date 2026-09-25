<?php
/**
 * Test if outbound API calls work on this host.
 * Upload to InfinityFree and visit in browser.
 */
header('Content-Type: text/plain');

echo "=== Outbound Connection Test ===\n\n";

// Test 1: Check if cURL is enabled
echo "1. cURL extension loaded: " . (extension_loaded('curl') ? 'YES' : 'NO') . "\n";

// Test 2: Try connecting to Groq API
$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'model' => 'llama-3.3-70b-versatile',
    'messages' => [['role' => 'user', 'content' => 'Hi']],
    'max_tokens' => 5
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer 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'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "2. Groq API HTTP code: " . ($httpCode ?: 'NONE') . "\n";
echo "3. cURL error: " . ($curlError ?: 'None') . "\n";
echo "4. Response: " . substr($response ?: 'EMPTY', 0, 300) . "\n\n";

// Test 3: Try a simple outbound request
$ch2 = curl_init('https://httpbin.org/get');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_TIMEOUT, 10);
curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, false);
$resp2 = curl_exec($ch2);
$err2 = curl_error($ch2);
$http2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "5. httpbin.org test - HTTP code: " . ($http2 ?: 'NONE') . "\n";
echo "6. httpbin.org error: " . ($err2 ?: 'None') . "\n";

if ($curlError || $httpCode === 0) {
    echo "\n=== DIAGNOSIS ===\n";
    echo "Your host is BLOCKING outbound API requests.\n";
    echo "The AI assistant will NOT work on this hosting plan.\n";
    echo "You need a host that allows outbound HTTPS calls.\n";
} elseif ($httpCode === 200) {
    echo "\n=== DIAGNOSIS ===\n";
    echo "Groq API is reachable. The issue may be your API key or request.\n";
} else {
    echo "\n=== DIAGNOSIS ===\n";
    echo "Got HTTP $httpCode from Groq. Check API key or rate limits.\n";
}
