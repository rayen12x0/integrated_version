<?php
// Comprehensive API Route Tester
// This endpoint tests all API routes to ensure they're accessible

header("Content-Type: application/json");

$test_results = [];
$errors_occurred = false;

// Database connection test
try {
    require_once '../config/config.php';
    $pdo = Config::getConnexion();
    $db_connected = true;
    $test_results['database'] = [
        'status' => 'success',
        'message' => 'Database connection successful'
    ];
} catch (Exception $e) {
    $db_connected = false;
    $errors_occurred = true;
    $test_results['database'] = [
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage()
    ];
}

// Define API endpoints to test
$endpoints = [
    'get_resources.php',
    'get_actions.php',
    'get_all_actions.php',
    'get_all_resources.php',
    'get_my_actions.php',
    'get_my_resources.php',
    'get_participated_actions.php',
    'check_auth.php',
    'create_action.php',
    'create_resource.php',
    'update_action.php',
    'update_resource.php',
    'delete_action.php',
    'delete_resource.php',
    'approve_action.php',
    'approve_resource.php',
    'get_comments.php',
    'add_comment.php',
    'delete_comment.php'
];

// Test each endpoint file existence
foreach ($endpoints as $endpoint) {
    $file_path = __DIR__ . '/' . $endpoint;
    $exists = file_exists($file_path);
    
    if ($exists) {
        // Test if we can include the file without errors (only if database is connected)
        if ($db_connected) {
            ob_start();
            $old_error_reporting = error_reporting();
            error_reporting(0); // Suppress errors during test
            
            $success = true;
            $error_msg = '';
            
            try {
                require_once $file_path;
            } catch (Exception $e) {
                $success = false;
                $error_msg = $e->getMessage();
            }
            
            error_reporting($old_error_reporting);
            ob_end_clean();
            
            $test_results[$endpoint] = [
                'status' => $success ? 'success' : 'error',
                'message' => $success ? 'File includes without error' : 'Error during inclusion: ' . $error_msg,
                'file_exists' => true
            ];
        } else {
            $test_results[$endpoint] = [
                'status' => 'skipped',
                'message' => 'Database not connected - skipped',
                'file_exists' => true
            ];
        }
    } else {
        $test_results[$endpoint] = [
            'status' => 'missing',
            'message' => 'File does not exist',
            'file_exists' => false
        ];
        $errors_occurred = true;
    }
}

// File path verification for commonly included files
$required_files = [
    '../config/config.php',
    '../controllers/actionController.php', 
    '../controllers/resourceController.php',
    '../model/action.php',
    '../model/resource.php',
    '../utils/imageUpload.php',
    '../utils/AuthHelper.php'
];

foreach ($required_files as $file) {
    $full_path = __DIR__ . '/..' . $file;
    $exists = file_exists($full_path);
    
    $test_results['required_file_' . str_replace(['../', '/'], ['','_'], $file)] = [
        'file' => $file,
        'status' => $exists ? 'exists' : 'missing',
        'full_path' => $full_path,
        'accessible' => $exists
    ];
    
    if (!$exists) {
        $errors_occurred = true;
    }
}

// Return results
$result = [
    'timestamp' => date('Y-m-d H:i:s'),
    'total_tests' => count($test_results),
    'errors_occurred' => $errors_occurred,
    'results' => $test_results
];

echo json_encode($result, JSON_PRETTY_PRINT);
?>