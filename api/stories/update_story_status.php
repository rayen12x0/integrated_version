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
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (!$input || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Story ID is required"
        ]);
        exit;
    }
    
    // Prepare the input for update status method
    $_POST['id'] = $input['id'];
    $_POST['status'] = $input['status'] ?? 'rejected';
    if (isset($input['admin_notes'])) {
        $_POST['admin_notes'] = $input['admin_notes'];
    }
    
    $controller->updateStatus();
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}