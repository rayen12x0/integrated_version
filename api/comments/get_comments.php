<?php
// Turn off error display to prevent HTML from being output
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in API responses
ini_set('log_errors', 1); // Log errors to file

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once __DIR__ . '/../../controllers/CommentController.php';

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
    error_log("Error in get_comments.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error occurred: " . $e->getMessage()
    ]);
}
