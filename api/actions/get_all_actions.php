<?php
// Turn off error display to prevent HTML from being output
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in API responses
ini_set('log_errors', 1); // Log errors to file

// API endpoint to get all actions (admin view)
// This file connects the frontend to the action controller

require_once __DIR__ . '/../../controllers/actionController.php';
require_once __DIR__ . '/../../utils/AuthHelper.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if user is authenticated
if (!AuthHelper::isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "User not authenticated"
    ]);
    exit;
}

try {
    $controller = new ActionController();
    $actions = $controller->getAll();

    echo json_encode([
        "success" => true,
        "actions" => $actions
    ]);
} catch (Exception $e) {
    error_log("Error in get_all_actions.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}