<?php
// API endpoint to get user's comments
// This file connects the frontend to the comment controller

// Set the content type header first
header('Content-Type: application/json; charset=utf-8');

// Create controller and handle request
try {
    // Require statements - According to the analysis, these paths need to be fixed
    require_once __DIR__ . '/../../controllers/CommentController.php';
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../model/comment.php';
    require_once __DIR__ . '/../../utils/AuthHelper.php';

    // Create controller instance and call get by user method
    $controller = new CommentController();
    $controller->getByUser();
} catch (Exception $e) {
    // If there's any error, return proper JSON
    echo json_encode([
        "success" => false,
        "message" => "Server error occurred: " . $e->getMessage()
    ]);
}
