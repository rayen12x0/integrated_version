<?php
// API endpoint to approve or reject a resource
// This file connects the frontend to the resource controller

// Suppress all PHP errors to prevent them from corrupting JSON responses
error_reporting(0);
ini_set('display_errors', 0);
session_start();

// Set the content type header immediately
header('Content-Type: application/json; charset=utf-8');

try {
    // Require statements - According to the analysis, these paths need to be fixed
    require_once __DIR__ . '/../../controllers/ResourceController.php';
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../model/resource.php';
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

    // Create controller and handle request
    $controller = new ResourceController();
    $controller->approve();
} catch (Exception $e) {
    // If there's any error, return proper JSON
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error occurred: " . $e->getMessage()
    ]);
}
?>
