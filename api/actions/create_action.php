<?php
// Suppress all PHP errors to prevent them from corrupting JSON responses
error_reporting(0);
// API endpoint to create a new action
// This file connects the frontend to the controller

// Capture any output to prevent it from corrupting JSON response
ob_start();

require_once __DIR__ . '/../../controllers/actionController.php';
require_once __DIR__ . '/../../utils/AuthHelper.php';

// Create controller instance and call create method
$controller = new ActionController();
$controller->create();

// Clean any output that might have been captured
ob_clean();

// Ensure we're sending JSON content type
header('Content-Type: application/json; charset=utf-8');

// The controller should handle output properly, but if not, send a fallback
if (ob_get_level()) {
    $output = ob_get_contents();
    if (!empty($output) && !headers_sent()) {
        // If there was output and headers not sent yet, try to handle it
        if (strpos($output, '{') === 0 || strpos($output, '[') === 0) {
            // Output looks like JSON, send it
            echo $output;
        } else {
            // Output doesn't look like JSON, send an error
            echo json_encode(['success' => false, 'message' => 'Server error occurred']);
        }
    }
    ob_end_flush();
}
?>
