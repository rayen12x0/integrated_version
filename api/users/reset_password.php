<?php
// API endpoint to handle password reset

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
    $requiredFields = ['token', 'password', 'confirmPassword'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            ApiResponse::error("{$field} is required", 400);
        }
    }
    
    $token = trim($input['token']);
    $password = $input['password'];
    $confirmPassword = $input['confirmPassword'];
    
    // Validate password match
    if ($password !== $confirmPassword) {
        ApiResponse::error('Passwords do not match', 400);
    }
    
    // Validate password strength
    if (strlen($password) < 6) {
        ApiResponse::error('Password must be at least 6 characters long', 400);
    }
    
    // Connect to database
    $pdo = Config::getConnexion();
    
    // Check if the reset token exists and is not expired
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = :token AND expires_at > NOW()");
    $stmt->execute([':token' => $token]);
    $resetRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$resetRecord) {
        ApiResponse::error('Invalid or expired reset token', 400);
    }
    
    // Hash the new password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Update user's password
    $stmt = $pdo->prepare("UPDATE users SET password_hash = :password_hash WHERE email = :email");
    $result = $stmt->execute([
        ':password_hash' => $hashedPassword,
        ':email' => $resetRecord['email']
    ]);
    
    if ($result) {
        // Delete the used token record
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = :token");
        $stmt->execute([':token' => $token]);
        
        ApiResponse::success([], 'Password has been reset successfully', 200);
    } else {
        ApiResponse::error('Failed to update password. Please try again.', 500);
    }
    
} catch (Exception $e) {
    // Clean any output that might have been captured
    if (ob_get_level()) {
        ob_clean();
    }
    
    ApiResponse::error('Password reset error: ' . $e->getMessage(), 500);
}

// Clean any output that might have been captured
if (ob_get_level()) {
    ob_end_clean();
}
?>