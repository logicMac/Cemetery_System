<?php
/**
 * Visitor AI Assistant API
 * Handles natural language queries for visitors
 */

header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/groq_config.php';

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';
$records = $input['records'] ?? [];
$plots = $input['plots'] ?? [];

if (empty($userMessage)) {
    echo json_encode(['success' => false, 'error' => 'No message provided']);
    exit;
}

// Build context for AI
$recordsSummary = "Cemetery has " . count($records) . " burial records.\n";
if (count($records) > 0) {
    $recordsSummary .= "Here are all the burial records you can search:\n";
    foreach ($records as $record) {
        $name = $record['decedent_name'];
        $plot = $record['plot_number'] ?? 'Unknown';
        $lat = $record['latitude'];
        $lng = $record['longitude'];
        $family = $record['family_name'] ?? '';
        $recordsSummary .= "- {$name} (Family: {$family}, Plot: {$plot}, Coordinates: {$lat},{$lng})\n";
    }
}

$plotsSummary = "Available plots for reservation: " . count($plots) . " plots.\n";

$systemPrompt = "You are MemoryGuide, an intelligent AI assistant for Matinao Memorial Cemetery. You help visitors find their loved ones' burial locations and provide navigation assistance.

Cemetery Information:
- Location: Matinao, Davao City, Philippines
- Center coordinates: 6.18344118743717, 125.08457146469357
- Operating hours: 6:00 AM to 6:00 PM daily
- Contact: Available at the cemetery office

{$recordsSummary}
{$plotsSummary}

IMPORTANT INSTRUCTIONS:
1. When a user asks to find someone (e.g., \"Where is Juan Dela Cruz?\", \"Take me to Maria Santos\", \"Find John Doe's grave\"), YOU MUST:
   - Search through the burial records list above
   - Find the person's name (match partial names if needed)
   - Respond with their location details
   - Include the navigation command: [NAV_TO: latitude, longitude, person_name]

2. Navigation Command Format:
   [NAV_TO: 6.18344, 125.08457, Juan Dela Cruz]
   
3. Example responses:
   - \"I found Juan Dela Cruz! He is buried at Plot 123. I'll navigate you there now. [NAV_TO: 6.18344, 125.08457, Juan Dela Cruz]\"
   - \"I found Maria Santos in the records. She's at Plot 45A. Let me guide you there. [NAV_TO: 6.18300, 125.08400, Maria Santos]\"

4. If you can't find an exact match, suggest similar names from the records.

5. Be compassionate, respectful, and helpful. Remember visitors are looking for their loved ones.

6. You can also answer general questions about the cemetery, available plots, and provide directions.";

// Prepare messages for Groq API
$messages = [
    ['role' => 'system', 'content' => $systemPrompt],
    ['role' => 'user', 'content' => $userMessage]
];

// Call Groq API
$apiResponse = sendGroqRequest($messages, 0.7, 512);

// Log the full response for debugging
error_log("Groq API Response: " . print_r($apiResponse, true));

if (!$apiResponse['success']) {
    // Log the specific error
    error_log("Groq API Error: " . ($apiResponse['error'] ?? 'Unknown error'));
    error_log("Groq API Response Body: " . ($apiResponse['response'] ?? 'No response'));
    
    echo json_encode([
        'success' => false,
        'error' => 'AI service unavailable',
        'response' => 'I apologize, but I\'m having trouble processing your request right now. Please try asking the cemetery staff for assistance.',
        'debug' => [
            'error' => $apiResponse['error'] ?? 'Unknown error',
            'api_key_present' => defined('GROQ_API_KEY') && !empty(GROQ_API_KEY)
        ]
    ]);
    exit;
}

// Extract response
$aiResponse = $apiResponse['data']['choices'][0]['message']['content'] ?? 'I apologize, but I could not generate a response.';

// Check for navigation command
$navigation = extractNavigationCommand($aiResponse);

// Remove navigation command from display text
if ($navigation) {
    $aiResponse = preg_replace('/\[NAV_TO:\s*[-\d.]+,\s*[-\d.]+\]/', '', $aiResponse);
    $aiResponse = trim($aiResponse);
}

echo json_encode([
    'success' => true,
    'response' => $aiResponse,
    'navigation' => $navigation
]);
