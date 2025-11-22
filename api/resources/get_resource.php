<?php
// API endpoint to get a specific resource by ID for editing
// This file connects the frontend to the controller

require_once "../../controllers/resourceController.php";

// Get resource ID from request
$id = $_GET['id'] ?? null;

if (!$id) {
    header("Content-Type: application/json");
    echo json_encode([
        "success" => false,
        "message" => "Resource ID is required"
    ]);
    exit;
}

// Create controller instance and call getById method
$controller = new ResourceController();
$controller->getById($id);
