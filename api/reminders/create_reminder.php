<?php
session_start();

// Set the content type header first
header('Content-Type: application/json; charset=utf-8');

try {
    // Require statements - According to the analysis, these paths need to be fixed
    require_once __DIR__ . '/../../controllers/ReminderController.php';
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../model/reminder.php';
    require_once __DIR__ . '/../../utils/AuthHelper.php';

    $controller = new ReminderController();
    $controller->create();
} catch (Exception $e) {
    // If there's any error, return proper JSON
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error occurred: " . $e->getMessage()
    ]);
}
?>