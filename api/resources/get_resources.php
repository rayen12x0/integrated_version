<?php
// Turn off error display to prevent HTML from being output
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in API responses
ini_set('log_errors', 1); // Log errors to file

// API endpoint to get approved resources (for public display)
// This file connects the frontend to the controller

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../controllers/resourceController.php';

error_log('get_resources.php called');

try {
    $controller = new ResourceController();
    $resources = $controller->getApproved();

    // Format response to match frontend expectation - keeping same structure as original
    echo json_encode([
        "success" => true,
        "resources" => $resources
    ]);
} catch (Exception $e) {
    error_log("Error in get_resources.php: " . $e->getMessage());
    // Return error in same format as success
    echo json_encode([
        "success" => false,
        "message" => "Failed to retrieve resources: " . $e->getMessage()
    ]);
}