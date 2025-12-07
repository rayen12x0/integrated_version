<?php
// API endpoint to handle forgot password request

error_reporting(0); // Suppress PHP errors that may cause JSON parsing issues
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../utils/ApiResponse.php';
require_once __DIR__ . '/../../utils/email_helper.php'; // Assuming we have an email helper

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
    
    // Validate required field
    if (!isset($input['email']) || empty(trim($input['email']))) {
        ApiResponse::error('Email is required', 400);
    }
    
    $email = trim($input['email']);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        ApiResponse::error('Invalid email format', 400);
    }
    
    // Connect to database
    $pdo = Config::getConnexion();
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Don't reveal if the email exists for security reasons
        ApiResponse::success([], 'If an account with this email exists, a reset link has been sent', 200);
    }
    
    // Generate password reset token
    $token = bin2hex(random_bytes(32)); // 64 character hex string
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour
    
    // Store the reset token in the database
    $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires_at)");
    $result = $stmt->execute([
        ':email' => $email,
        ':token' => $token,
        ':expires_at' => $expiresAt
    ]);
    
    if ($result) {
        // In a real application, send email with reset link
        // For demo purposes, we'll return the token so it can be tested
        $resetLink = "http://localhost/my_work_v2/auth/reset_password.html?token=" . $token;
        
        // For demo purposes only - in production, send the link via email
        ApiResponse::success([
            'reset_link' => $resetLink
        ], 'Password reset link has been sent to your email address', 200);
        
        // Uncomment below code for real email sending in production
        /*
        $subject = "Password Reset Request - Connect for Peace";
        $message = "Hi {$user['name']},\n\nA password reset request was made for your account. Please click the link below to reset your password:\n\n{$resetLink}\n\nThis link will expire in 1 hour.\n\nIf you didn't request this, please ignore this email.";
        
        $emailSent = sendEmail($email, $subject, $message);
        
        if ($emailSent) {
            ApiResponse::success([], 'Password reset link has been sent to your email address', 200);
        } else {
            ApiResponse::error('Failed to send reset email. Please try again.', 500);
        }
        */
    } else {
        ApiResponse::error('Failed to process your request. Please try again.', 500);
    }
    
} catch (Exception $e) {
    // Clean any output that might have been captured
    if (ob_get_level()) {
        ob_clean();
    }
    
    ApiResponse::error('Forgot password error: ' . $e->getMessage(), 500);
}

// Clean any output that might have been captured
if (ob_get_level()) {
    ob_end_clean();
}
?>