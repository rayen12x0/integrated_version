<?php
// Suppress all PHP errors to prevent them from corrupting JSON responses
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', 0);
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

    // Create controller and handle request
    $controller = new ReportController();
    $controller->getAll();
} catch (Exception $e) {
    // If there's any error, return proper JSON
    echo json_encode([
        "success" => false,
        "message" => "Server error occurred: " . $e->getMessage()
    ]);
}
?>