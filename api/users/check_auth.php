<?php
// Turn off error display to prevent HTML from being output
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in API responses
ini_set('log_errors', 1); // Log errors to file

// API endpoint to check authentication status (for vue frontend)
// Now uses session-based authentication with fallbacks

require_once "../../utils/AuthHelper.php";

header("Content-Type: application/json");
// Add CORS headers if needed
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

try {
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
} catch (Exception $e) {
    error_log("Error in check_auth.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "authenticated" => false,
        "message" => "Authentication check failed: " . $e->getMessage()
    ]);
}
