<?php
// api/comments/get_story_comments.php
// API endpoint to get comments for a specific story

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../controllers/CommentController.php';
require_once __DIR__ . '/../../utils/ApiResponse.php';

try {
    $controller = new CommentController();

    // Get story_id from query parameters
    $storyId = $_GET['story_id'] ?? null;

    if (!$storyId) {
        // Try getting from POST body as well
        $input = json_decode(file_get_contents("php://input"), true);
        $storyId = $input['story_id'] ?? null;
    }

    if (!$storyId) {
        ApiResponse::error("Missing story_id parameter", 400);
        exit();
    }

    // Get comments for the story
    $controller->getByEntity(null, null, $storyId); // actionId=null, resourceId=null, storyId=provided value

} catch (Exception $e) {
    ApiResponse::error("Error retrieving comments: " . $e->getMessage(), 500);
}
?>