<?php
// API endpoint to set user session
error_reporting(0); // Suppress PHP errors that may cause JSON parsing issues
ini_set('display_errors', 0);

require_once __DIR__ . '/../../utils/AuthHelper.php';
require_once __DIR__ . '/../../utils/ApiResponse.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Capture any output to prevent it from corrupting JSON response
ob_start();

try {
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['user_id'])) {
        ApiResponse::error('Invalid input data. User ID is required.', 400);
    }
    
    $userId = $input['user_id'];
    
    // Validate user ID
    if (!in_array($userId, [1, 2])) { // Only allow IDs 1 and 2 for demo purposes
        ApiResponse::error('Invalid user ID. Only IDs 1 and 2 are supported.', 400);
    }
    
    // Start session and set user
    $user = AuthHelper::startSession($userId);
    
    // Success response
    ApiResponse::success([
        'user' => $user,
        'authenticated' => true
    ], 'Session set successfully', 200);
    
} catch (Exception $e) {
    // Clean any output that might have been captured
    if (ob_get_level()) {
        ob_clean();
    }
    
    ApiResponse::error('Error setting session: ' . $e->getMessage(), 500);
}

// Clean any output that might have been captured
if (ob_get_level()) {
    ob_end_clean();
}
?>