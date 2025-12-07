<?php
// API endpoint to change user password
// This file connects the frontend to the profile controller

// Set the content type header first
header('Content-Type: application/json; charset=utf-8');

// Create controller and handle request
try {
    // Require statements 
    require_once __DIR__ . '/../../controllers/ProfileController.php';
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../utils/AuthHelper.php';


    $controller = new ProfileController();
    $controller->updatePassword();
} catch (Exception $e) {
    // If there's any error, return proper JSON
    echo json_encode([
        "success" => false,
        "message" => "Server error occurred: " . $e->getMessage()
    ]);
}