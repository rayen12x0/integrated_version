<?php
// API endpoint to get user's own actions for dashboard
// For admin users, returns all actions
// For regular users, returns only their own actions

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/actionController.php';
require_once __DIR__ . '/../../utils/AuthHelper.php';

// Get user ID from query parameter (for testing purposes)
$user_id = $_GET['user_id'] ?? 2; // Default to user ID 2
$is_admin = false;

if ($user_id == 1) {
    $is_admin = true;
}

try {
    $controller = new ActionController();

    if ($is_admin) {
        // Admin sees all actions
        $actions = $controller->getAll();
    } else {
        // Regular user sees only their own actions
        $actions = $controller->getByCreatorId($user_id);
    }

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