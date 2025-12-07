<?php
// API endpoint to add a comment to an action or resource
// This file connects the frontend to the comment controller

// Set the content type header first
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {

    // Require statements - According to the analysis, these paths need to be fixed
    require_once __DIR__ . '/../../controllers/CommentController.php';
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../model/comment.php';
    require_once __DIR__ . '/../../utils/AuthHelper.php';


    $controller = new CommentController();
    $controller->create();
} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Internal server error occurred: " . $e->getMessage()
    ]);
}

?>
