<?php
// API endpoint to get comments for an action or resource
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

    // Get input parameters
    $actionId = $_GET['action_id'] ?? null;
    $resourceId = $_GET['resource_id'] ?? null;


    $controller = new CommentController();

    if ($actionId || $resourceId) {
        // Get comments by entity (action or resource)
        $controller->getByEntity($actionId, $resourceId);
    } else {
        $controller->getAll();
    }
} catch (Exception $e) {
    // If there's any error, return proper JSON
    echo json_encode([
        "success" => false,
        "message" => "Server error occurred: " . $e->getMessage()
    ]);
}
