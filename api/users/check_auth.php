<?php
// API endpoint to check authentication status (for vue frontend)
// Now uses session-based authentication with fallbacks

require_once "../../utils/AuthHelper.php";

header("Content-Type: application/json");
// Add CORS headers if needed
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Use AuthHelper to get current user (checks session first, then URL params, then defaults)
$user = AuthHelper::getCurrentUser();

// Determine if user is authenticated based on whether they have an ID
$authenticated = $user['id'] !== null;

if (!$authenticated) {
    http_response_code(401);
}

// Return authentication status with user data
echo json_encode([
    "success" => true,
    "authenticated" => $authenticated,
    "user" => $user,
    "isAdmin" => $user['role'] === 'admin'
]);
