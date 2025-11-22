<?php
// API endpoint to approve or reject a resource
// This file connects the frontend to the controller

require_once "../controllers/resourceController.php";
require_once "../utils/AuthHelper.php";

// Verify admin role
$currentUser = AuthHelper::getCurrentUser();
if (!AuthHelper::isAdmin($currentUser)) {
    header("Content-Type: application/json");
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized. Admin access required."
    ]);
    exit;
}

// Create controller instance and call approve method
$controller = new ResourceController();
$controller->approve();