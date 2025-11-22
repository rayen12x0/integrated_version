<?php
// API endpoint to get user's own actions for dashboard
// For admin users, returns all actions
// For regular users, returns only their own actions

require_once '../../controllers/actionController.php';

// For testing purposes, determine if user is admin based on mock data
// In a real system, this would come from session/auth
$user_id = $_GET['user_id'] ?? 2; // Default to user ID 2
$is_admin = false; 

if ($user_id == 1) {
    $is_admin = true;
}

$controller = new ActionController();

if ($is_admin) {
    // Admin sees all actions
    $controller->getAll();
} else {
    // Regular user sees only their own actions
    $controller->getByCreatorId($user_id);
}
