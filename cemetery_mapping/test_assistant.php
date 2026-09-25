<?php
/**
 * Test visitor assistant API directly.
 * Upload to InfinityFree and visit in browser.
 */
header('Content-Type: text/plain');

echo "=== Visitor Assistant API Test ===\n\n";

// Simulate a request to visitor_assistant.php
$testMessage = "Hello, can you help me?";

// Simulate the API call internally
require_once 'config/database.php';
require_once 'config/groq_config.php';

echo "1. API Key defined: " . (defined('GROQ_API_KEY') ? 'YES (' . strlen(GROQ_API_KEY) . ' chars)' : 'NO') . "\n";
echo "2. API Key starts with gsk_: " . (strpos(GROQ_API_KEY, 'gsk_') === 0 ? 'YES' : 'NO') . "\n";
echo "3. Model: " . GROQ_MODEL . "\n";
echo "4. API URL: " . GROQ_API_URL . "\n\n";

// Test a simple Groq request
$messages = [
    ['role' => 'system', 'content' => 'You are a helpful assistant. Reply briefly.'],
    ['role' => 'user', 'content' => $testMessage]
];

echo "5. Sending test request to Groq...\n";
$result = sendGroqRequest($messages, 0.7, 50);

echo "6. Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";

if ($result['success']) {
    $aiText = $result['data']['choices'][0]['message']['content'] ?? 'No content';
    echo "7. AI Response: " . substr($aiText, 0, 200) . "\n";
    echo "\n=== RESULT ===\n";
    echo "Groq API is working! The AI assistant should function.\n";
    echo "If it still fails, the issue is in visitor_assistant.php or visitor.js.\n";
} else {
    echo "7. Error: " . ($result['error'] ?? 'Unknown') . "\n";
    if (isset($result['response'])) {
        echo "8. Raw response: " . substr($result['response'], 0, 500) . "\n";
    }
    echo "\n=== RESULT ===\n";
    echo "Groq API call failed. Check the error above.\n";
    if (strpos($result['error'] ?? '', '401') !== false) {
        echo "The API key is invalid. Get a new one at https://console.groq.com/keys\n";
    } elseif (strpos($result['error'] ?? '', '429') !== false) {
        echo "Rate limit hit. Wait a minute and try again.\n";
    } elseif (strpos($result['error'] ?? '', 'timeout') !== false) {
        echo "Request timed out. The server may be slow.\n";
    }
}
