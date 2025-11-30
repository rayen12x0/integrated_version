<?php
// API endpoint to get all resources (admin view)
// This file connects the frontend to the resource controller

require_once __DIR__ . '/../../controllers/resourceController.php';
require_once __DIR__ . '/../../utils/AuthHelper.php';

// Check if user is authenticated
if (!AuthHelper::isLoggedIn()) {
    echo json_encode([
        "success" => false,
        "message" => "User not authenticated"
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    $controller = new ResourceController();
    $resources = $controller->getAll();

    echo json_encode([
        "success" => true,
        "resources" => $resources
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}