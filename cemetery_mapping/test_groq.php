<?php
/**
 * Test GROQ API Connection
 */

require_once 'config/groq_config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test GROQ API</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #1a1a2e;
            color: white;
            max-width: 900px;
            margin: 0 auto;
        }
        .section {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .success { color: #22c55e; }
        .error { color: #ef4444; }
        .warning { color: #fbbf24; }
        pre {
            background: #000;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            white-space: pre-wrap;
        }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <h1>GROQ API Connection Test</h1>
    
    <?php
    // Check if API key is set
    echo '<div class="section">';
    echo '<h2>1. API Key Check</h2>';
    
    if (defined('GROQ_API_KEY') && !empty(GROQ_API_KEY)) {
        $keyStart = substr(GROQ_API_KEY, 0, 10);
        $keyEnd = substr(GROQ_API_KEY, -4);
        echo "<p class='success'>✓ API Key is set: {$keyStart}...{$keyEnd}</p>";
    } else {
        echo "<p class='error'>✗ API Key is NOT set!</p>";
        echo "<p>Please add your GROQ API key to config/groq_config.php</p>";
    }
    
    echo "<p><strong>API URL:</strong> " . GROQ_API_URL . "</p>";
    echo "<p><strong>Model:</strong> " . GROQ_MODEL . "</p>";
    echo '</div>';
    
    // Check cURL support
    echo '<div class="section">';
    echo '<h2>2. cURL Support Check</h2>';
    
    if (function_exists('curl_version')) {
        $version = curl_version();
        echo "<p class='success'>✓ cURL is enabled</p>";
        echo "<p>Version: {$version['version']}</p>";
        echo "<p>SSL: " . ($version['ssl_version'] ?? 'Not available') . "</p>";
    } else {
        echo "<p class='error'>✗ cURL is NOT enabled!</p>";
        echo "<p>Please enable cURL in your PHP configuration</p>";
    }
    
    echo '</div>';
    
    // Test API connection
    echo '<div class="section">';
    echo '<h2>3. API Connection Test</h2>';
    
    if (defined('GROQ_API_KEY') && !empty(GROQ_API_KEY) && function_exists('curl_version')) {
        echo "<p>Testing connection to GROQ API...</p>";
        
        $testMessages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant.'],
            ['role' => 'user', 'content' => 'Say "Hello from Matinao Cemetery!" and nothing else.']
        ];
        
        $response = sendGroqRequest($testMessages, 0.5, 50);
        
        echo "<h3>API Response:</h3>";
        
        if ($response['success']) {
            echo "<p class='success'>✓ API connection successful!</p>";
            
            $aiMessage = $response['data']['choices'][0]['message']['content'] ?? 'No content';
            echo "<p><strong>AI Response:</strong> $aiMessage</p>";
            
            echo "<h4>Full Response Data:</h4>";
            echo "<pre>" . json_encode($response['data'], JSON_PRETTY_PRINT) . "</pre>";
        } else {
            echo "<p class='error'>✗ API connection failed!</p>";
            echo "<p><strong>Error:</strong> " . ($response['error'] ?? 'Unknown error') . "</p>";
            
            if (isset($response['response'])) {
                echo "<h4>Response Body:</h4>";
                echo "<pre>" . htmlspecialchars($response['response']) . "</pre>";
            }
        }
    } else {
        echo "<p class='warning'>⚠ Cannot test - API key or cURL not available</p>";
    }
    
    echo '</div>';
    
    // Test cemetery query
    echo '<div class="section">';
    echo '<h2>4. Cemetery Query Test</h2>';
    
    if (defined('GROQ_API_KEY') && !empty(GROQ_API_KEY) && function_exists('curl_version')) {
        echo "<p>Testing cemetery-specific query...</p>";
        
        $cemeteryMessages = [
            ['role' => 'system', 'content' => 'You are a helpful AI assistant for Matinao Memorial Cemetery. Answer questions about the cemetery.'],
            ['role' => 'user', 'content' => 'How many total burial records do we have?']
        ];
        
        $response = sendGroqRequest($cemeteryMessages, 0.7, 200);
        
        if ($response['success']) {
            echo "<p class='success'>✓ Cemetery query successful!</p>";
            
            $aiMessage = $response['data']['choices'][0]['message']['content'] ?? 'No content';
            echo "<p><strong>AI Response:</strong></p>";
            echo "<pre>$aiMessage</pre>";
        } else {
            echo "<p class='error'>✗ Query failed!</p>";
            echo "<p><strong>Error:</strong> " . ($response['error'] ?? 'Unknown error') . "</p>";
        }
    } else {
        echo "<p class='warning'>⚠ Cannot test - API key or cURL not available</p>";
    }
    
    echo '</div>';
    ?>
    
    <div class="section">
        <h2>Troubleshooting</h2>
        
        <h3>If API Key is Missing:</h3>
        <ol>
            <li>Go to <a href="https://console.groq.com" target="_blank" style="color: #667eea;">https://console.groq.com</a></li>
            <li>Sign up or log in</li>
            <li>Go to API Keys section</li>
            <li>Create a new API key</li>
            <li>Copy the key</li>
            <li>Open <code>config/groq_config.php</code></li>
            <li>Replace the placeholder with your actual API key</li>
        </ol>
        
        <h3>If cURL is Disabled:</h3>
        <ol>
            <li>Find your <code>php.ini</code> file</li>
            <li>Search for <code>;extension=curl</code></li>
            <li>Remove the semicolon to enable: <code>extension=curl</code></li>
            <li>Restart Apache/WAMP</li>
        </ol>
        
        <h3>If API Connection Fails:</h3>
        <ul>
            <li>Check your internet connection</li>
            <li>Verify your API key is valid</li>
            <li>Check if your firewall is blocking api.groq.com</li>
            <li>Try accessing https://api.groq.com in your browser</li>
        </ul>
    </div>
    
    <div class="section">
        <h2>Quick Actions</h2>
        <p><a href="visitor/dashboard.php" style="color: #667eea;">→ Try Visitor Dashboard</a></p>
        <p><a href="admin/assistant.php" style="color: #667eea;">→ Try Admin Assistant</a></p>
        <p><button onclick="location.reload()">🔄 Refresh Test</button></p>
    </div>
</body>
</html>
