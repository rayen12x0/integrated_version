<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../controllers/StoryController.php';
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../utils/AuthHelper.php';
    
    $controller = new StoryController();
    $controller->update();
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}