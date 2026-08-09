<?php
/**
 * Admin AI Assistant API
 * Handles admin queries about statistics and operations
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';
require_once '../config/groq_config.php';

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';

if (empty($userMessage)) {
    echo json_encode(['success' => false, 'error' => 'No message provided']);
    exit;
}

// Gather system statistics
try {
    $totalRecords = $pdo->query("SELECT COUNT(*) FROM burial_records")->fetchColumn();
    $totalPlots = $pdo->query("SELECT COUNT(*) FROM available_plots")->fetchColumn();
    $premiumPlots = $pdo->query("SELECT COUNT(*) FROM burial_records WHERE is_fenced = 1")->fetchColumn();
    $thisMonth = $pdo->query("SELECT COUNT(*) FROM burial_records WHERE MONTH(date_added) = MONTH(CURRENT_DATE()) AND YEAR(date_added) = YEAR(CURRENT_DATE())")->fetchColumn();
    
    $byBarangay = $pdo->query("
        SELECT barangay, COUNT(*) as count 
        FROM burial_records 
        WHERE barangay IS NOT NULL 
        GROUP BY barangay 
        ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $byYear = $pdo->query("
        SELECT YEAR(death_date) as year, COUNT(*) as count 
        FROM burial_records 
        WHERE death_date IS NOT NULL AND YEAR(death_date) >= YEAR(CURRENT_DATE()) - 5
        GROUP BY YEAR(death_date) 
        ORDER BY year DESC
    ")->fetchAll(PDO::FETCH_KEY_PAIR);
    
} catch (PDOException $e) {
    error_log("Assistant stats error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit;
}

// Build context for AI
$statsContext = "Cemetery Management Statistics:\n\n";
$statsContext .= "Total Burial Records: {$totalRecords}\n";
$statsContext .= "Available Plots: {$totalPlots}\n";
$statsContext .= "Premium/Fenced Plots: {$premiumPlots}\n";
$statsContext .= "Standard Plots: " . ($totalRecords - $premiumPlots) . "\n";
$statsContext .= "Records Added This Month: {$thisMonth}\n\n";

$statsContext .= "Records by Barangay:\n";
foreach ($byBarangay as $barangay => $count) {
    $statsContext .= "- {$barangay}: {$count} records\n";
}

$statsContext .= "\nRecords by Year (Death Date):\n";
foreach ($byYear as $year => $count) {
    $statsContext .= "- {$year}: {$count} deaths\n";
}

$systemPrompt = "You are an AI assistant for cemetery management administrators. You help analyze data, provide insights, and answer questions about cemetery operations.

{$statsContext}

When providing statistics:
- Be precise with numbers
- Offer insights and trends
- Suggest actionable recommendations
- Format responses clearly with bullet points when appropriate

If asked about specific records or detailed queries, explain that you can provide general statistics but recommend using the search or records page for specific information.

Be professional, concise, and helpful.";

$messages = [
    ['role' => 'system', 'content' => $systemPrompt],
    ['role' => 'user', 'content' => $userMessage]
];

// Call Groq API
$apiResponse = sendGroqRequest($messages, 0.7, 1024);

if (!$apiResponse['success']) {
    echo json_encode([
        'success' => false,
        'error' => 'AI service unavailable',
        'response' => 'I apologize, but I\'m having trouble processing your request. Here are the current statistics:\n\n' .
                     "Total Records: {$totalRecords}\n" .
                     "Available Plots: {$totalPlots}\n" .
                     "Premium Plots: {$premiumPlots}\n" .
                     "This Month: {$thisMonth}"
    ]);
    exit;
}

$aiResponse = $apiResponse['data']['choices'][0]['message']['content'] ?? 'I could not generate a response.';

// Check if response should include chart data
$chartData = null;
if (stripos($userMessage, 'barangay') !== false) {
    $chartData = $byBarangay;
} elseif (stripos($userMessage, 'year') !== false || stripos($userMessage, 'trend') !== false) {
    $chartData = $byYear;
}

echo json_encode([
    'success' => true,
    'response' => $aiResponse,
    'chart_data' => $chartData
]);
