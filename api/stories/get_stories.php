<?php
// api/stories/get_stories.php
// Get stories with optional filtering by status and search term
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once __DIR__ . '/../../controllers/StoryController.php';
    require_once __DIR__ . '/../../model/Story.php';
    require_once __DIR__ . '/../../utils/AuthHelper.php';

    $controller = new StoryController();

    // Get query parameters for filtering
    $status = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';

    // If search is provided, we need to handle it differently (filter after fetching)
    if ($search) {
        // Instantiate story model directly to fetch and filter stories
        require_once __DIR__ . '/../../config/config.php';
        $config = new Config();
        $pdo = $config->getConnexion();
        $storyModel = new Story($pdo);

        // Get stories based on status
        if ($status === 'pending') {
            $stories = $storyModel->getPending();
        } elseif ($status === 'approved') {
            $stories = $storyModel->getApproved();
        } elseif ($status === 'all') {
            $stories = $storyModel->getAll();
        } else {
            // Default to approved stories
            $stories = $storyModel->getApproved();
        }

        if ($stories !== false && $stories !== null) {
            // Apply search filter
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
            
            // Add reaction counts to each story
            foreach ($stories as &$story) {
                $reactionCounts = $storyModel->getReactionCounts($story['id']);
                $story['reaction_counts'] = $reactionCounts;
            }

            // Return filtered stories
            echo json_encode([
                'success' => true,
                'stories' => $stories,
                'message' => 'Stories retrieved successfully',
                'count' => count($stories),
                'status_code' => 200
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'stories' => [],
                'message' => 'No stories found',
                'count' => 0,
                'status_code' => 200
            ]);
        }
    } else {
        // If no search parameter, use the controller method which handles its own response
        if ($status === 'pending') {
            $controller->getPending();
        } elseif ($status === 'approved') {
            $controller->getApproved();
        } elseif ($status === 'all') {
            $controller->getAll();
        } else {
            // Default to approved stories
            $controller->getApproved();
        }
    }
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>