<?php
session_start();

// Set the content type header first
header('Content-Type: application/json; charset=utf-8');

try {
    // Require statements
    require_once __DIR__ . '/../../controllers/ReminderController.php';
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../model/reminder.php';
    require_once __DIR__ . '/../../utils/AuthHelper.php';

    $controller = new ReminderController();

    // Check if it's a GET request with ID in URL parameters
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

    if ($id) {
        // If ID is provided in URL, call the get method
        $controller->getById($id);
    } else {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
        if ($id) {
            $controller->getById($id);
        } else {
            // If no ID provided, return error
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Reminder ID is required"
            ]);
        }
    }
} catch (Exception $e) {
    // If there's any error, return proper JSON
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error occurred: " . $e->getMessage()
    ]);
}
?>