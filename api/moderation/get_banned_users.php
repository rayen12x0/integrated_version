<?php
// API endpoint to get banned users
// This file connects the frontend to the moderation controller

// Set the content type header first
header('Content-Type: application/json; charset=utf-8');

// Create controller and handle request
try {
    // Require statements 
    require_once __DIR__ . '/../../controllers/ModerationController.php';
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../utils/AuthHelper.php';


    $controller = new ModerationController();
    $controller->getBannedUsers();
} catch (Exception $e) {
    // If there's any error, return proper JSON
    echo json_encode([
        "success" => false,
        "message" => "Server error occurred: " . $e->getMessage()
    ]);
}