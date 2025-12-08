<?php
// controllers/StoryController.php
// Story Controller to handle business logic for stories, mirroring the ActionController pattern

require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../model/Story.php";
require_once __DIR__ . "/../utils/imageUpload.php";
require_once __DIR__ . "/../utils/AuthHelper.php";
require_once __DIR__ . "/../utils/ApiResponse.php";

class StoryController
{
    private $pdo;
    private $story;

    // Constructor initializes database connection and story model
    public function __construct() {
        // Add CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        $this->pdo = Config::getConnexion();
        $this->story = new Story($this->pdo);
    }

    // Handle input for both JSON and file uploads
    private function getStoryData() {

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {

            $data = [];
            foreach ($_POST as $key => $value) {
                $data[$key] = $value;
            }

            // Process image upload if present
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = ImageUpload::uploadImage($_FILES['image'], 'stories');
                if ($uploadResult['success']) {
                    $data['image_url'] = $uploadResult['image_url'];
                } else {
                    throw new Exception($uploadResult['message']);
                }
            }

            return $data;
        } else {
            // Handle JSON input
            $input = json_decode(file_get_contents("php://input"), true);
            if (!$input) {
                // For file uploads, PHP might not parse JSON properly, so check if we have POST data that's not JSON
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_FILES['image'])) {
                    // This could be invalid JSON, so we'll try to get it raw and check
                    $rawData = file_get_contents("php://input");
                    if (!empty($rawData)) {
                        throw new Exception("Invalid JSON input: " . $rawData);
                    }
                }
                // If it's an empty JSON body, we'll handle it in the create method
                return [];
            }
            return $input;
        }
    }

    // index() : List all published stories (with filters)
    public function index() {
        $this->getAll();
    }

    // show($id) : Display single story with reactions
    public function show($id) {
        $this->getById($id);
    }

    // create() : Show create story form (API: return form config/metadata)
    // Also aliased to store() for backward compatibility with existing API endpoints
    public function create() {
        // If it's a POST request, treat it as store()
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->store();
        } else {
            header("Content-Type: application/json");
            ApiResponse::success(['schema' => 'story_create_schema'], 'Create story form ready', 200);
        }
    }

    // store() : Save new story (POST)
    public function store() {
        error_log("StoryController::store() called.");

        header("Content-Type: application/json");

        try {
            // Get current user from authentication system
            $currentUser = AuthHelper::getCurrentUser();
            if (!$currentUser || !isset($currentUser['id']) || !$currentUser['id']) {
                error_log("User not authenticated for story creation");
                ApiResponse::error("Authentication required to create a story", 401);
                return;
            }

            // Get input data (handles both JSON and form data)
            $input = $this->getStoryData();
            error_log("Received input: " . print_r($input, true));

            // Define required fields (excluding creator_id since it's derived from auth)
            $required = ["title", "content", "theme", "author_name"];

            // Check required fields
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    error_log("Missing required field: $field");
                    ApiResponse::error("Missing required field: $field", 400);
                    return;
                }
            }

            // Set creator_id from the authenticated user
            $input['creator_id'] = $currentUser['id'];

            // Set default values for optional fields
            if (!isset($input['excerpt']) && !empty($input['content'])) {
                $input['excerpt'] = $this->generateExcerpt($input['content']);
            }

            if (!isset($input['author_avatar'])) {
                $input['author_avatar'] = $this->generateAvatarFromName($input['author_name']);
            }

            $input['language'] = $input['language'] ?? 'en';
            $input['privacy'] = $input['privacy'] ?? 'public';
            $input['status'] = $input['status'] ?? 'pending'; // Default to pending for approval

            // Create record in database
            error_log("Attempting to create story with data: " . print_r($input, true));
            $result = $this->story->create($input);

            if ($result) {
                require_once __DIR__ . "/../model/notification.php"; // Include notification model
                $notification = new Notification($this->pdo); // Initialize notification model

                // Success response
                $lastId = $this->pdo->lastInsertId();
                error_log("Story created successfully with ID: " . $lastId);

                // Create notification for story creation
                $notification->createStoryCreatedNotification($input['creator_id'], $lastId, $input['title']);

                ApiResponse::success(['id' => $lastId], 'Story created successfully', 201);
            } else {
                // Error response
                error_log("Failed to create story in model.");
                ApiResponse::error('Failed to create story', 400);
            }
        } catch (Exception $e) {
            error_log("Error in create story: " . $e->getMessage());
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    // edit($id) : Show edit story form
    public function edit($id) {
        $this->getById($id);
    }

    // Get story by ID method
    public function getById($id) {
        header("Content-Type: application/json");

        try {
            $story = $this->story->findById($id);
            if ($story) {
                // Increment view count
                $this->story->incrementViews($id);
                
                ApiResponse::success($story, 'Story retrieved successfully', 200);
            } else {
                ApiResponse::error('Story not found', 404);
            }
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    // Get stories by creator ID method (for user dashboard)
    public function getByCreatorId($creator_id) {
        try {
            $stories = $this->story->getByCreatorId($creator_id);
            // Return the data for the API endpoint to format
            return $stories;
        } catch (Exception $e) {
            // Throw exception for the API endpoint to handle
            throw $e;
        }
    }

    // Get approved stories method (for public display)
    public function getApproved() {
        header("Content-Type: application/json");

        try {
            $stories = $this->story->getApproved();

            // Send the response directly
            if ($stories !== false && $stories !== null) {
                echo json_encode([
                    "success" => true,
                    "stories" => $stories,
                    "message" => "Approved stories retrieved successfully",
                    "count" => is_array($stories) ? count($stories) : 0
                ]);
            } else {
                echo json_encode([
                    "success" => false,
                    "stories" => [],
                    "message" => "No approved stories found",
                    "count" => 0
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Server error: " . $e->getMessage()
            ]);
        }
    }

    // Get all stories method
    public function getAll() {
        header("Content-Type: application/json");

        try {
            // Check if status parameter is provided in GET
            $status = $_GET['status'] ?? null;

            if ($status === 'pending') {
                $stories = $this->story->getPending();
            } else {
                $stories = $this->story->getAll();
            }

            // Send the response directly
            if ($stories !== false && $stories !== null) {
                echo json_encode([
                    "success" => true,
                    "stories" => $stories,
                    "message" => "Stories retrieved successfully",
                    "count" => is_array($stories) ? count($stories) : 0
                ]);
            } else {
                echo json_encode([
                    "success" => false,
                    "stories" => [],
                    "message" => "No stories found",
                    "count" => 0
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Server error: " . $e->getMessage()
            ]);
        }
    }

    // Get pending stories method (for admin dashboard)
    public function getPending() {
        header("Content-Type: application/json");

        try {
            $stories = $this->story->getPending();

            if ($stories !== false && $stories !== null) {
                echo json_encode([
                    "success" => true,
                    "stories" => $stories,
                    "message" => "Pending stories retrieved successfully",
                    "count" => count($stories)
                ]);
            } else {
                echo json_encode([
                    "success" => false,
                    "stories" => [],
                    "message" => "No pending stories found or error retrieving stories",
                    "count" => 0
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Server error: " . $e->getMessage()
            ]);
        }
    }

    // Update story method
    public function update() {
        header("Content-Type: application/json");

        try {
            // Get input data (handles both JSON and form data)
            $input = $this->getStoryData();

            if (!$input || !isset($input['id'])) {
                ApiResponse::error("Invalid input or missing story ID", 400);
                return;
            }

            $id = $input['id'];
            unset($input['id']); // Remove ID from data to be updated

            // Verify ownership or admin role
            $currentUser = AuthHelper::getCurrentUser();
            $existingStory = $this->story->findById($id);

            if (!$existingStory) {
                ApiResponse::error("Story not found", 404);
                return;
            }

            if (!AuthHelper::isAdmin($currentUser) && !AuthHelper::isOwner($existingStory['creator_id'], $currentUser['id'])) {
                ApiResponse::error("Unauthorized. You can only edit your own stories.", 403);
                return;
            }

            // Required fields for story update
            // For now, let's assume all fields are optional for updates
            // Set default values for any missing fields
            if (isset($input['content']) && !isset($input['excerpt'])) {
                $input['excerpt'] = $this->generateExcerpt($input['content']);
            }
            
            if (isset($input['author_name']) && !isset($input['author_avatar'])) {
                $input['author_avatar'] = $this->generateAvatarFromName($input['author_name']);
            }

            // Get the existing story before update to get the title
            $existingStory = $this->story->findById($id);

            $result = $this->story->update($id, $input);

            if ($result) {
                require_once __DIR__ . "/../model/notification.php"; // Include notification model
                $notification = new Notification($this->pdo); // Initialize notification model

                // Create notification for story update (only for the creator)
                $currentUser = AuthHelper::getCurrentUser();
                if ($existingStory && $existingStory['creator_id'] == $currentUser['id']) {
                    $notification->createStoryUpdatedNotification($existingStory['creator_id'], $id, $input['title'] ?? $existingStory['title']);
                }

                ApiResponse::success(null, 'Story updated successfully', 200);
            } else {
                ApiResponse::error('Failed to update story or story not found', 400);
            }
        } catch (Exception $e) {
            error_log("Error in update story: " . $e->getMessage());
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    // Approve story method (for admin dashboard)
    public function approve() {
        header("Content-Type: application/json");
        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input || !isset($input['id']) || !isset($input['action'])) {
            ApiResponse::error("Invalid JSON input or missing story ID or action", 400);
            return;
        }

        $id = $input['id'];
        $action = $input['action']; // 'approve' or 'reject'

        if ($action === 'approve') {
            // Get the existing story before approve to get the title and creator_id
            $existingStory = $this->story->findById($id);

            $result = $this->story->approve($id);
            $message = $result ? "Story approved successfully" : "Failed to approve story";

            if ($result && $existingStory) {
                require_once __DIR__ . "/../model/notification.php"; // Include notification model
                $notification = new Notification($this->pdo); // Initialize notification model

                // Create notification for story approval
                $notification->createStoryApprovedNotification($existingStory['creator_id'], $id, $existingStory['title']);
            }
        } elseif ($action === 'reject') {
            // Get the existing story before reject to get the title and creator_id
            $existingStory = $this->story->findById($id);

            $result = $this->story->reject($id);
            $message = $result ? "Story rejected successfully" : "Failed to reject story";

            if ($result && $existingStory) {
                require_once __DIR__ . "/../model/notification.php"; // Include notification model
                $notification = new Notification($this->pdo); // Initialize notification model

                // Create notification for story rejection
                $notification->createStoryRejectedNotification($existingStory['creator_id'], $id, $existingStory['title']);
            }
        } else {
            ApiResponse::error("Invalid action. Use 'approve' or 'reject'.", 400);
            return;
        }

        if ($result) {
            ApiResponse::success(null, $message, 200);
        } else {
            ApiResponse::error($message, 400);
        }
    }

    public function updateStatus() {
        header("Content-Type: application/json");

        // Get input from $_POST (set by API endpoints)
        $id = $_POST['id'] ?? null;
        $status = $_POST['status'] ?? null;
        $adminNotes = $_POST['admin_notes'] ?? null;

        if (!$id || !$status) {
            ApiResponse::error("Missing required fields: id and status", 400);
            return;
        }

        // Validate status
        $validStatuses = ['pending', 'approved', 'rejected'];
        if (!in_array($status, $validStatuses)) {
            ApiResponse::error("Invalid status. Must be: pending, approved, or rejected", 400);
            return;
        }

        // Get existing story
        $existingStory = $this->story->findById($id);
        if (!$existingStory) {
            ApiResponse::error("Story not found", 404);
            return;
        }

        // Update status in database
        $updateData = ['status' => $status];
        if ($adminNotes) {
            $updateData['admin_notes'] = $adminNotes;
        }

        $result = $this->story->update($id, $updateData);

        if ($result) {
            require_once __DIR__ . "/../model/notification.php";
            $notification = new Notification($this->pdo);

            // Create appropriate notification
            if ($status === 'approved') {
                $notification->createStoryApprovedNotification(
                    $existingStory['creator_id'],
                    $id,
                    $existingStory['title']
                );
            } elseif ($status === 'rejected') {
                $notification->createStoryRejectedNotification(
                    $existingStory['creator_id'],
                    $id,
                    $existingStory['title']
                );
            }

            ApiResponse::success(null, "Story status updated to {$status}", 200);
        } else {
            ApiResponse::error("Failed to update story status", 400);
        }
    }

    // Delete story method
    public function delete() {
        header("Content-Type: application/json");
        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input || !isset($input['id'])) {
            ApiResponse::error("Invalid JSON input or missing story ID", 400);
            return;
        }

        $id = $input['id'];

        // Verify ownership or admin role
        $currentUser = AuthHelper::getCurrentUser();
        $existingStory = $this->story->findById($id);

        if (!$existingStory) {
            ApiResponse::error("Story not found", 404);
            return;
        }

        if (!AuthHelper::isAdmin($currentUser) && !AuthHelper::isOwner($existingStory['creator_id'], $currentUser['id'])) {
            ApiResponse::error("Unauthorized. You can only delete your own stories.", 403);
            return;
        }

        // Get the existing story before deletion to get the title and creator_id
        $existingStory = $this->story->findById($id);

        $result = $this->story->delete($id);

        if ($result) {
            require_once __DIR__ . "/../model/notification.php"; // Include notification model
            $notification = new Notification($this->pdo); // Initialize notification model

            // Create notification for story deletion
            if ($existingStory) {
                $notification->createStoryDeletedNotification($existingStory['creator_id'], $existingStory['title']);
            }

            ApiResponse::success(null, "Story deleted successfully", 200);
        } else {
            ApiResponse::error("Failed to delete story or story not found", 400);
        }
    }

    // Helper method to generate excerpt from content
    private function generateExcerpt($content, $length = 150) {
        if (strlen($content) <= $length) {
            return $content;
        }
        return substr($content, 0, $length) . '...';
    }

    // Helper method to generate avatar from name
    private function generateAvatarFromName($name) {
        $words = explode(' ', trim($name));
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        } else {
            return strtoupper(substr($name, 0, 2));
        }
    }
}