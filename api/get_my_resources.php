<?php
// API endpoint to get user's own resources for dashboard
// For admin users, returns all resources
// For regular users, returns only their own resources

require_once '../controllers/resourceController.php';

// For testing purposes, determine if user is admin based on mock data
// In a real system, this would come from session/auth
$user_id = $_GET['user_id'] ?? 2; 
$is_admin = false; 

if ($user_id == 1) {
    $is_admin = true; 
}

$controller = new ResourceController();

if ($is_admin) {
    // Admin sees all resources
    $controller->getAll();
} else {
    // Regular user sees only their own resources
    $controller->getByPublisherId($user_id);
}