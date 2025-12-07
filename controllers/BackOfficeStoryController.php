<?php
// controllers/BackOfficeStoryController.php
// Admin Story Controller for back-office management

require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../model/Story.php";
require_once __DIR__ . "/../model/StoryReaction.php";
require_once __DIR__ . "/../utils/AuthHelper.php";
require_once __DIR__ . "/../utils/ApiResponse.php";
require_once __DIR__ . "/../utils/imageUpload.php";

class BackOfficeStoryController {
    private $pdo;
    private $story;
    private $storyReaction;

    public function __construct() {
        // Add CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        $this->pdo = Config::getConnexion();
        $this->story = new Story($this->pdo);
        $this->storyReaction = new StoryReaction($this->pdo);
    }

    // Ensure admin access
    private function requireAdmin() {
        $currentUser = AuthHelper::getCurrentUser();
        if (!AuthHelper::isAdmin($currentUser)) {
            ApiResponse::error("Unauthorized access. Admin privileges required.", 403);
            exit;
        }
        return $currentUser;
    }

    /**
     * index() : Admin stories dashboard with stats
     */
    public function index() {
        $this->requireAdmin();
        header("Content-Type: application/json");

        try {
            // Get statistics
            $stats = $this->story->getStatistics();
            
            // Get all stories with details
            $stories = $this->story->getAll();

            ApiResponse::success([
                'stats' => $stats,
                'stories' => $stories
            ], 'Admin dashboard data retrieved successfully', 200);
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * show($id) : View story details with reaction analysis
     */
    public function show($id) {
        $this->requireAdmin();
        header("Content-Type: application/json");

        try {
            $story = $this->story->findById($id);
            if (!$story) {
                ApiResponse::error("Story not found", 404);
                return;
            }

            // Get detailed reaction analysis
            $reactions = $this->storyReaction->getByStory($id);
            
            // Get detailed timeline of reactions (if model supports it, otherwise mock or implement)
            // Assuming Story model has getDetailedReactions or we use StoryReaction
            // The user spec says Story model has getDetailedReactions
            $timeline = [];
            if (method_exists($this->story, 'getDetailedReactions')) {
                $timeline = $this->story->getDetailedReactions($id);
            }

            ApiResponse::success([
                'story' => $story,
                'reactions' => $reactions,
                'timeline' => $timeline
            ], 'Story details retrieved successfully', 200);
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * create() : Admin create story form
     * In API context, this might return form configuration or just be a placeholder.
     */
    public function create() {
        $this->requireAdmin();
        header("Content-Type: application/json");
        // Return form schema or configuration if needed
        ApiResponse::success(['schema' => 'story_create_schema'], 'Create story form ready', 200);
    }

    /**
     * store() : Save story (POST)
     */
    public function store() {
        $currentUser = $this->requireAdmin();
        header("Content-Type: application/json");

        try {
            $input = $this->getStoryData();
            
            // Validation
            if (empty($input['title']) || empty($input['content'])) {
                ApiResponse::error("Title and content are required", 400);
                return;
            }

            $input['creator_id'] = $currentUser['id'];
            $input['status'] = $input['status'] ?? 'approved'; // Admins publish directly by default

            if ($this->story->create($input)) {
                $lastId = $this->pdo->lastInsertId();
                ApiResponse::success(['id' => $lastId], 'Story created successfully by admin', 201);
            } else {
                ApiResponse::error("Failed to create story", 500);
            }
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * edit($id) : Admin edit story form
     */
    public function edit($id) {
        $this->requireAdmin();
        header("Content-Type: application/json");

        try {
            $story = $this->story->findById($id);
            if ($story) {
                ApiResponse::success($story, 'Story data for edit retrieved', 200);
            } else {
                ApiResponse::error("Story not found", 404);
            }
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * update() : Update story (POST)
     */
    public function update() {
        $this->requireAdmin();
        header("Content-Type: application/json");

        try {
            $input = $this->getStoryData();
            
            if (!isset($input['id'])) {
                ApiResponse::error("Story ID is required", 400);
                return;
            }

            $id = $input['id'];
            unset($input['id']);

            if ($this->story->update($id, $input)) {
                ApiResponse::success(null, 'Story updated successfully by admin', 200);
            } else {
                ApiResponse::error("Failed to update story", 500);
            }
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * delete($id) : Delete story
     */
    public function delete($id) {
        $this->requireAdmin();
        header("Content-Type: application/json");

        try {
            if ($this->story->delete($id)) {
                ApiResponse::success(null, 'Story deleted successfully by admin', 200);
            } else {
                ApiResponse::error("Failed to delete story", 500);
            }
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    // Helper to process input
    private function getStoryData() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
            $data = $_POST;
            if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = ImageUpload::uploadImage($_FILES['image'], 'stories');
                if ($uploadResult['success']) {
                    $data['image_url'] = $uploadResult['image_url'];
                }
            }
            return $data;
        } else {
            return json_decode(file_get_contents("php://input"), true) ?? [];
        }
    }
}
