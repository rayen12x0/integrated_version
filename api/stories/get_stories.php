<?php
// api/stories/get_stories.php
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

$controller = new StoryController();

// Get query parameters for filtering
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Determine which method to call based on status parameter
if ($status === 'pending') {
    $stories = $controller->getPending();
} elseif ($status === 'approved') {
    $stories = $controller->getApproved();
} elseif ($status === 'all') {
    $stories = $controller->getAll(); // Get all stories regardless of status
} else {
    // If no specific status is requested, get approved stories by default
    $stories = $controller->getApproved();
}

// Apply search filter if provided
if ($search && $stories !== false) {
    $searchTerm = strtolower($search);
    $filteredStories = [];

    foreach ($stories as $story) {
        // Search in title, content, author_name
        if (strpos(strtolower($story['title'] ?? ''), $searchTerm) !== false ||
            strpos(strtolower($story['content'] ?? ''), $searchTerm) !== false ||
            strpos(strtolower($story['author_name'] ?? ''), $searchTerm) !== false) {
            $filteredStories[] = $story;
        }
    }

    $stories = $filteredStories;
}

// Add reaction counts to each story
if ($stories !== false) {
    // Initialize Story model to get reaction counts
    require_once __DIR__ . '/../../config/config.php';
    $config = new Config();
    $pdo = $config->getConnexion();
    $storyModel = new Story($pdo);

    foreach ($stories as &$story) {
        $reactionCounts = $storyModel->getReactionCounts($story['id']);
        $story['reaction_counts'] = $reactionCounts;
    }
}

// Format the response to be compatible with the frontend expectations
require_once __DIR__ . '/../../utils/ApiResponse.php';
if ($stories !== false) {
    // Make sure we return stories in the format expected by frontend
    // The frontend expects result.stories format
    echo json_encode([
        'success' => true,
        'stories' => $stories,
        'message' => 'Stories retrieved successfully',
        'status_code' => 200
    ]);
} else {
    ApiResponse::error('Failed to retrieve stories', 500);
}
?>