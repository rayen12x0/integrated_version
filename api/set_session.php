<?php
// API endpoint to set user session
// This endpoint stores user information in PHP session

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

// Get JSON input
$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input['user_id'])) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "user_id is required"
    ]);
    exit;
}

$user_id = (int)$input['user_id'];

// Validate user_id
if (!in_array($user_id, [1, 2])) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid user_id. Must be 1 (admin) or 2 (user)"
    ]);
    exit;
}

try {
    // Store user_id in session
    $_SESSION['user_id'] = $user_id;
    
    // Create user object based on user_id
    if ($user_id == 1) {
        $user = [
            "id" => 1,
            "name" => "Admin User",
            "email" => "admin@example.com",
            "role" => "admin",
            "avatar_url" => "https://api.placeholder.com/40/40?text=AU",
            "badge" => "Administrator"
        ];
    } else {
        $user = [
            "id" => 2,
            "name" => "Regular User",
            "email" => "user@example.com",
            "role" => "user",
            "avatar_url" => "https://api.placeholder.com/40/40?text=RU",
            "badge" => "Community Member"
        ];
    }
    
    // Store user object in session
    $_SESSION['user'] = $user;
    
    // Log session creation for debugging
    error_log("Session created for user_id: " . $user_id);
    
    // Return success response
    echo json_encode([
        "success" => true,
        "message" => "Session created successfully",
        "user" => $user
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error setting session: " . $e->getMessage()
    ]);
}