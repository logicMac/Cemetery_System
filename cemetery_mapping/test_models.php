<?php
/**
 * Test multiple Groq models to find which ones work on this account.
 * Upload to InfinityFree and visit in browser.
 */
header('Content-Type: text/plain');

require_once 'config/database.php';
require_once 'config/groq_config.php';

echo "=== Groq Model Availability Test ===\n\n";
echo "API Key: " . (defined('GROQ_API_KEY') ? 'Present (' . strlen(GROQ_API_KEY) . ' chars)' : 'MISSING') . "\n";
echo "Current configured model: " . GROQ_MODEL . "\n\n";

$models = [
    'llama-3.1-8b-instant',
    'llama-3.3-70b-versatile',
    'llama3-8b-8192',
    'llama3-70b-8192',
    'meta-llama/llama-4-scout-17b-16e-instruct',
    'meta-llama/llama-4-maverick-17b-128e-instruct',
    'openai/gpt-oss-120b',
    'openai/gpt-oss-20b',
    'qwen/qwen3-32b',
    'gemma2-9b-it',
    'mixtral-8x7b-32768',
];

$working = [];

foreach ($models as $model) {
    $data = [
        'model' => $model,
        'messages' => [['role' => 'user', 'content' => 'Say hi']],
        'max_tokens' => 10
    ];

    $ch = curl_init(GROQ_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . GROQ_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $status = '';
    $note = '';

    if ($httpCode === 200) {
        $status = 'OK';
        $working[] = $model;
    } elseif ($httpCode === 404) {
        $status = 'NOT FOUND';
    } elseif ($httpCode === 429) {
        $status = 'RATE LIMITED (try later)';
        $working[] = $model; // Model exists, just rate limited
    } else {
        $status = "HTTP $httpCode";
        $decoded = json_decode($response, true);
        if (isset($decoded['error']['message'])) {
            $note = ' - ' . substr($decoded['error']['message'], 0, 80);
        }
    }

    echo str_pad($model, 50) . " => $status$note\n";
}

echo "\n=== WORKING MODELS ===\n";
if (empty($working)) {
    echo "NONE! Your API key may be invalid or your account has no model access.\n";
    echo "Check your key at https://console.groq.com/keys\n";
} else {
    foreach ($working as $m) {
        echo "  - $m\n";
    }
    echo "\nRECOMMENDED: Update config/groq_config.php with one of these:\n";
    echo "  define('GROQ_MODEL', '" . $working[0] . "');\n";
}
