<?php
// api/stories/get_my_stories.php
// Turn off error display to prevent HTML from being output
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in API responses
ini_set('log_errors', 1); // Log errors to file

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../controllers/StoryController.php';
require_once __DIR__ . '/../../utils/AuthHelper.php';

$controller = new StoryController();

// Get current user
$currentUser = AuthHelper::getCurrentUser();
if (!$currentUser || $currentUser['id'] === null) {
    require_once __DIR__ . '/../../utils/ApiResponse.php';
    ApiResponse::error('User not authenticated', 401);
    exit();
}

// Get stories by creator ID
$stories = $controller->getByCreatorId($currentUser['id']);

// Format the response
require_once __DIR__ . '/../../utils/ApiResponse.php';
if ($stories !== false) {
    ApiResponse::success($stories, 'My stories retrieved successfully', 200);
} else {
    ApiResponse::error('Failed to retrieve stories', 500);
}
?>