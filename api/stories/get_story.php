<?php
// api/stories/get_story.php
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
require_once __DIR__ . '/../../model/Story.php';
require_once __DIR__ . '/../../utils/AuthHelper.php';
require_once __DIR__ . '/../../utils/ApiResponse.php';

$controller = new StoryController();

// Get story ID from query parameters or POST data
$storyId = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $storyId = $_GET['id'] ?? null;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    $storyId = $input['id'] ?? null;
}

if ($storyId) {
    // Get story by ID
    require_once __DIR__ . '/../../config/config.php';
    $config = new Config();
    $pdo = $config->getConnexion();
    $storyModel = new Story($pdo);

    $story = $storyModel->findById($storyId);

    if ($story) {
        // Add reaction counts to the story
        $reactionCounts = $storyModel->getReactionCounts($story['id']);
        $story['reaction_counts'] = $reactionCounts;

        ApiResponse::success($story, 'Story retrieved successfully', 200);
    } else {
        ApiResponse::error('Story not found', 404);
    }
} else {
    // If no ID provided, return error
    ApiResponse::error("Story ID is required", 400);
}
?>