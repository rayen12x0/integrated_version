<?php
// API endpoint to add a comment to an action or resource
// This file connects the frontend to the comment controller

// Start output buffering to capture any potential error output
ob_start();

// Set JSON header immediately to ensure it's sent before any potential errors
header("Content-Type: application/json");


ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    
    require_once "../controllers/CommentController.php";

    
    $controller = new CommentController();
    $controller->create();
} catch (Exception $e) {
    
    http_response_code(500);
    
    ob_clean();
    echo json_encode([
        "success" => false,
        "message" => "Internal server error occurred: " . $e->getMessage()
    ]);
}


ob_end_flush();