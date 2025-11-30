<?php
session_start();

// Set the content type header first
header('Content-Type: application/json; charset=utf-8');

// Create controller and handle request
try {
    // Require statements - According to the analysis, these paths need to be fixed
    require_once __DIR__ . '/../../controllers/ReportController.php';
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../model/report.php';
    require_once __DIR__ . '/../../utils/AuthHelper.php';

    $controller = new ReportController();
    $controller->updateStatus();
} catch (Exception $e) {
    // If there's any error, return proper JSON
    echo json_encode([
        "success" => false,
        "message" => "Server error occurred: " . $e->getMessage()
    ]);
}
?>