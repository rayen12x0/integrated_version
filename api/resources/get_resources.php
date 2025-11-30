<?php
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

$controller = new ResourceController();
$resources = $controller->getApproved();

// Format response to match frontend expectation
echo json_encode([
    "success" => true,
    "resources" => $resources
], JSON_PRETTY_PRINT);