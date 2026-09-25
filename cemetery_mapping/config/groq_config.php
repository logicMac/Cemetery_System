<?php
/**
 * Groq AI Configuration
 * Configuration for Groq API integration.
 *
 * API credentials are now stored in the `ai_api_keys` table and managed
 * from Admin -> API Keys. The file constants below act as a fallback when
 * the table is missing or has no active 'groq' row.
 */

// Fallback credentials (used only if the database has no active Groq key)
// The API key lives in groq_key.local.php (gitignored). Never commit real keys.
$_groqLocalKey = '';
$__groqKeyFile = __DIR__ . '/groq_key.local.php';
if (is_file($__groqKeyFile)) {
    // groq_key.local.php should define GROQ_API_KEY or GROQ_LOCAL_API_KEY
    include $__groqKeyFile;
    if (defined('GROQ_API_KEY')) $_groqLocalKey = GROQ_API_KEY;
    elseif (defined('GROQ_LOCAL_API_KEY')) $_groqLocalKey = GROQ_LOCAL_API_KEY;
}
define('GROQ_API_KEY_FALLBACK', $_groqLocalKey);
define('GROQ_API_URL_FALLBACK', 'https://api.groq.com/openai/v1/chat/completions');
define('GROQ_MODEL_FALLBACK', 'openai/gpt-oss-120b');

// Resolve credentials: database first, file fallback second
$_groqKey = GROQ_API_KEY_FALLBACK;
$_groqUrl = GROQ_API_URL_FALLBACK;
$_groqModel = GROQ_MODEL_FALLBACK;

try {
    if (!isset($pdo)) {
        require_once __DIR__ . '/database.php';
    }
    $stmt = $pdo->query(
        "SELECT api_key, api_url, model FROM ai_api_keys
         WHERE provider = 'groq' AND is_active = 1
         ORDER BY id DESC LIMIT 1"
    );
    $row = $stmt->fetch();
    if ($row && !empty($row['api_key'])) {
        $_groqKey = $row['api_key'];
        if (!empty($row['api_url'])) $_groqUrl = $row['api_url'];
        if (!empty($row['model']))   $_groqModel = $row['model'];
    }
} catch (Exception $e) {
    // Table may not exist yet — fall back to file constants
    error_log('ai_api_keys lookup failed: ' . $e->getMessage());
}

define('GROQ_API_KEY', $_groqKey);
define('GROQ_API_URL', $_groqUrl);
define('GROQ_MODEL', $_groqModel);

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
