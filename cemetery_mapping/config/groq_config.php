<?php
/**
 * Groq AI Configuration
 * Configuration for Groq API integration using llama-3.1-70b-versatile model
 */

// Groq API Configuration
define('GROQ_API_KEY', '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'); // Replace with actual API key
define('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');
define('GROQ_MODEL', 'llama-3.3-70b-versatile'); // Updated to newer model

/**
 * Send request to Groq API
 * @param array $messages Array of message objects with role and content
 * @param float $temperature Response randomness (0-2)
 * @param int $max_tokens Maximum tokens in response
 * @return array API response
 */
function sendGroqRequest($messages, $temperature = 0.7, $max_tokens = 1024) {
    $data = [
        'model' => GROQ_MODEL,
        'messages' => $messages,
        'temperature' => $temperature,
        'max_tokens' => $max_tokens
    ];
    
    $ch = curl_init(GROQ_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . GROQ_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Fix for SSL certificate issue on Windows/WAMP
    // For production, download cacert.pem and use CURLOPT_CAINFO instead
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return [
            'success' => false,
            'error' => 'cURL Error: ' . $error
        ];
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return [
            'success' => false,
            'error' => 'API Error: HTTP ' . $httpCode,
            'response' => $response
        ];
    }
    
    $decoded = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'error' => 'JSON Decode Error: ' . json_last_error_msg()
        ];
    }
    
    return [
        'success' => true,
        'data' => $decoded
    ];
}

/**
 * Extract navigation command from AI response
 * Looks for [NAV_TO: lat, lng, name] pattern
 * @param string $text AI response text
 * @return array|null Navigation coordinates and name or null
 */
function extractNavigationCommand($text) {
    // Try to match with name: [NAV_TO: lat, lng, name]
    if (preg_match('/\[NAV_TO:\s*([-\d.]+),\s*([-\d.]+),\s*([^\]]+)\]/', $text, $matches)) {
        return [
            'lat' => floatval($matches[1]),
            'lng' => floatval($matches[2]),
            'name' => trim($matches[3])
        ];
    }
    // Fallback to old format: [NAV_TO: lat, lng]
    if (preg_match('/\[NAV_TO:\s*([-\d.]+),\s*([-\d.]+)\]/', $text, $matches)) {
        return [
            'lat' => floatval($matches[1]),
            'lng' => floatval($matches[2]),
            'name' => 'Destination'
        ];
    }
    return null;
}
