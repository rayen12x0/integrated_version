<?php
// API endpoint to register a new user

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
    $requiredFields = ['name', 'email', 'password'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            ApiResponse::error("{$field} is required", 400);
        }
    }
    
    $name = trim($input['name']);
    $email = trim($input['email']);
    $password = $input['password'];
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        ApiResponse::error('Invalid email format', 400);
    }
    
    // Validate password strength
    if (strlen($password) < 6) {
        ApiResponse::error('Password must be at least 6 characters long', 400);
    }
    
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Connect to database
    $pdo = Config::getConnexion();
    
    // Check if user already exists
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $checkStmt->execute([':email' => $email]);
    
    if ($checkStmt->fetch()) {
        ApiResponse::error('A user with this email address already exists', 409);
    }
    
    // Generate avatar URL using DiceBear API
    $avatarSeed = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '_', $name));
    if (empty($avatarSeed)) {
        $avatarSeed = 'user_' . time();
    }
    $avatarUrl = "https://api.dicebear.com/7.x/avataaars/svg?seed={$avatarSeed}";
    
    // Insert new user
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, avatar_url, created_at) VALUES (:name, :email, :password_hash, :avatar_url, NOW())");
    
    $result = $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':password_hash' => $hashedPassword,
        ':avatar_url' => $avatarUrl
    ]);
    
    if ($result) {
        $userId = $pdo->lastInsertId();
        
        // Return success response with user info
        ApiResponse::success([
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'avatar_url' => $avatarUrl
        ], 'Account created successfully', 201);
    } else {
        ApiResponse::error('Failed to create account. Please try again.', 500);
    }
    
} catch (Exception $e) {
    // Clean any output that might have been captured
    if (ob_get_level()) {
        ob_clean();
    }
    
    ApiResponse::error('Registration error: ' . $e->getMessage(), 500);
}

// Clean any output that might have been captured
if (ob_get_level()) {
    ob_end_clean();
}
?>