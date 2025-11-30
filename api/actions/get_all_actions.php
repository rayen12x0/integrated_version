<?php
// API endpoint to get all actions (admin view)
// This file connects the frontend to the action controller

require_once __DIR__ . '/../../controllers/actionController.php';
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
    $controller = new ActionController();
    $actions = $controller->getAll();

    echo json_encode([
        "success" => true,
        "actions" => $actions
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}