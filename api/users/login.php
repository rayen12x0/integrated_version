<?php
// API endpoint to authenticate user login

error_reporting(0); // Suppress PHP errors that may cause JSON parsing issues
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../utils/ApiResponse.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Capture any output to prevent it from corrupting JSON response
ob_start();

try {
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        ApiResponse::error('Invalid input data', 400);
    }
    
    // Validate required fields
    $requiredFields = ['email', 'password'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            ApiResponse::error("{$field} is required", 400);
        }
    }
    
    $email = trim($input['email']);
    $password = $input['password'];
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        ApiResponse::error('Invalid email format', 400);
    }
    
    // Connect to database
    $pdo = Config::getConnexion();
    
    // Prepare query to get user by email
    $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role, avatar_url, created_at FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        ApiResponse::error('Invalid email or password', 401);
    }
    
    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        ApiResponse::error('Invalid email or password', 401);
    }
    
    // Update last_login
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
    $stmt->execute([':id' => $user['id']]);
    
    // Prepare user data for response (exclude sensitive data)
    $userData = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'avatar_url' => $user['avatar_url'],
        'created_at' => $user['created_at']
    ];
    
    // Return success response with user info
    ApiResponse::success([
        'user' => $userData,
        'authenticated' => true
    ], 'Login successful', 200);
    
} catch (Exception $e) {
    // Clean any output that might have been captured
    if (ob_get_level()) {
        ob_clean();
    }
    
    ApiResponse::error('Login error: ' . $e->getMessage(), 500);
}

// Clean any output that might have been captured
if (ob_get_level()) {
    ob_end_clean();
}
?>