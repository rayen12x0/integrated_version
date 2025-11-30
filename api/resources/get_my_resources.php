<?php
// API endpoint to get user's own resources for dashboard
// For admin users, returns all resources
// For regular users, returns only their own resources

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/resourceController.php';
require_once __DIR__ . '/../../utils/AuthHelper.php';

// Get user ID from query parameter (for testing purposes)
$user_id = $_GET['user_id'] ?? 2;
$is_admin = false;

if ($user_id == 1) {
    $is_admin = true;
}

try {
    $controller = new ResourceController();

    if ($is_admin) {
        // Admin sees all resources
        $resources = $controller->getAll();
    } else {
        // Regular user sees only their own resources
        $resources = $controller->getByPublisherId($user_id);
    }

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