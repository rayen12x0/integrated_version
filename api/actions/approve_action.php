<?php
// API endpoint to approve or reject an action


// Suppress all PHP errors to prevent them from corrupting JSON responses
error_reporting(0);
ini_set('display_errors', 0);
session_start();

// Set the content type header immediately
header('Content-Type: application/json; charset=utf-8');

try {
    
    require_once __DIR__ . '/../../controllers/ActionController.php';
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../model/action.php';
    require_once __DIR__ . '/../../utils/AuthHelper.php';

    // Verify admin role
    $currentUser = AuthHelper::getCurrentUser();
    if (!AuthHelper::isAdmin($currentUser)) {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "message" => "Unauthorized. Admin access required."
        ]);
        exit;
    }

    
    $controller = new ActionController();
    $controller->approve();
} catch (Exception $e) {
    
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error occurred: " . $e->getMessage()
    ]);
}
?>
