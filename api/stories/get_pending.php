<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../controllers/StoryController.php';
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../utils/AuthHelper.php';
    
    // Check if user is admin
    if (!AuthHelper::isAdmin()) {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "message" => "Access denied. Admin privileges required."
        ]);
        exit;
    }
    
    $controller = new StoryController();
    
    // Set the status filter to 'pending' to get pending stories
    $_GET['status'] = 'pending';
    
    $controller->getAll();
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}