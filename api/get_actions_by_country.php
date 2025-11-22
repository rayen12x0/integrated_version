<?php
// API endpoint to get actions by country
require_once '../model/action.php';
require_once '../utils/AuthHelper.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Check if request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get country parameter
$country = $_GET['country'] ?? '';

if (empty($country)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Country parameter is required']);
    exit;
}

try {
    $action = new Action();
    
    // Get actions by country
    $actions = $action->getByCountry($country);
    
    echo json_encode([
        'success' => true,
        'actions' => $actions,
        'count' => count($actions)
    ]);
} catch (Exception $e) {
    error_log('Error getting actions by country: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving actions: ' . $e->getMessage()
    ]);
}
?>