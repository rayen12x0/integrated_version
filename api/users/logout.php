<?php
// API endpoint to handle user logout
// This endpoint destroys user session data

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Start session if not already started
if (session_id() == '') {
    session_start();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);
    exit;
}

try {
    // Log current user for debugging
    if (isset($_SESSION['user_id'])) {
        error_log("Logging out user_id: " . $_SESSION['user_id']);
    }
    
    // Destroy all session data
    $_SESSION = array();
    
    // If session was started with cookies, delete the cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Return success response
    echo json_encode([
        "success" => true,
        "message" => "Logged out successfully"
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error during logout: " . $e->getMessage()
    ]);
}
