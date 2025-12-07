<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../controllers/CommentController.php';
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../utils/AuthHelper.php';
    
    $controller = new CommentController();
    $controller->approveComment();
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}