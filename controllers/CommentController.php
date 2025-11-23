<?php
// controllers/CommentController.php
// Comment Controller to handle business logic for comments

require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../model/comment.php";

class CommentController
{
    private $pdo;
    private $comment;

    // Constructor initializes database connection and comment model
    public function __construct() {
        $this->pdo = Config::getConnexion();
        $this->comment = new Comment($this->pdo);
    }

    // Create comment method
    public function create() {
        error_log("CommentController::create() called.");
        // Set JSON header
        header("Content-Type: application/json");

        // Get JSON input
        $input = json_decode(file_get_contents("php://input"), true);
        error_log("Received input: " . print_r($input, true));

        // Validate input
        if (!$input) {
            error_log("Invalid JSON input received.");
            echo json_encode([
                "success" => false,
                "message" => "Invalid JSON input"
            ]);
            return;
        }

        // Required fields for comment creation
        $required = ["user_id", "content"];

        // Check if required fields are present
        foreach ($required as $field) {
            if (empty($input[$field])) {
                error_log("Missing required field: $field");
                echo json_encode([
                    "success" => false,
                    "message" => "Missing required field: $field"
                ]);
                return;
            }
        }

        // Either action_id or resource_id must be provided
        if (empty($input['action_id']) && empty($input['resource_id'])) {
            error_log("Either action_id or resource_id must be provided");
            echo json_encode([
                "success" => false,
                "message" => "Either action_id or resource_id must be provided"
            ]);
            return;
        }

        // Create record in database
        error_log("Attempting to create comment with data: " . print_r($input, true));
        $result = $this->comment->create($input);

        if ($result) {
            require_once __DIR__ . "/../model/notification.php"; // Include notification model
            $notification = new Notification($this->pdo); // Initialize notification model

            // Success response
            $lastId = $this->pdo->lastInsertId();
            error_log("Comment created successfully with ID: " . $lastId);

            // Get item title and creator for notification
            $itemId = null;
            $itemTitle = '';
            $creatorId = null;
            $itemType = '';

            if (!empty($input['action_id'])) {
                $itemType = 'action';
                $itemId = $input['action_id'];

                // Get action title and creator
                $stmt = $this->pdo->prepare("SELECT title, creator_id FROM actions WHERE id = :id");
                $stmt->execute([':id' => $itemId]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($item) {
                    $itemTitle = $item['title'];
                    $creatorId = $item['creator_id'];
                }
            } elseif (!empty($input['resource_id'])) {
                $itemType = 'resource';
                $itemId = $input['resource_id'];

                // Get resource title and publisher
                $stmt = $this->pdo->prepare("SELECT resource_name, publisher_id FROM resources WHERE id = :id");
                $stmt->execute([':id' => $itemId]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($item) {
                    $itemTitle = $item['resource_name'];
                    $creatorId = $item['publisher_id'];
                }
            }

            // Get user name who commented
            $userStmt = $this->pdo->prepare("SELECT name FROM users WHERE id = :user_id");
            $userStmt->execute([':user_id' => $input['user_id']]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            $commenterName = $user['name'] ?? 'A user';

            // Create notification for comment (only if the commenter is not the creator)
            if ($creatorId && $creatorId != $input['user_id']) {
                $notification->createCommentAddedNotification($creatorId, $itemId, $itemTitle, $commenterName, $itemType);
            }

            echo json_encode([
                "success" => true,
                "message" => "Comment created successfully",
                "id" => $lastId
            ]);
        } else {
            // Error response
            error_log("Failed to create comment in model.");
            echo json_encode([
                "success" => false,
                "message" => "Failed to create comment"
            ]);
        }
    }

    // Get all comments method
    public function getAll() {
        header("Content-Type: application/json");

        try {
            $comments = $this->comment->getAll();
            echo json_encode([
                "success" => true,
                "comments" => $comments
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    // Get comments by entity (action or resource)
    public function getByEntity() {
        header("Content-Type: application/json");

        $actionId = $_GET['action_id'] ?? null;
        $resourceId = $_GET['resource_id'] ?? null;

        // Either action_id or resource_id must be provided
        if (!$actionId && !$resourceId) {
            error_log("Either action_id or resource_id must be provided for get by entity");
            echo json_encode([
                "success" => false,
                "message" => "Either action_id or resource_id must be provided"
            ]);
            return;
        }

        try {
            $comments = $this->comment->getByEntity($actionId, $resourceId);
            echo json_encode([
                "success" => true,
                "comments" => $comments
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    // Get comment by ID method
    public function getById($id) {
        header("Content-Type: application/json");

        try {
            $comment = $this->comment->findById($id);
            if ($comment) {
                echo json_encode([
                    "success" => true,
                    "comment" => $comment
                ]);
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Comment not found"
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    // Update comment method
    public function update() {
        header("Content-Type: application/json");
        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input || !isset($input['id'])) {
            echo json_encode([
                "success" => false,
                "message" => "Invalid JSON input or missing comment ID"
            ]);
            return;
        }

        $id = $input['id'];
        unset($input['id']); // Remove ID from data to be updated

        // Required fields for comment update
        if (!isset($input['content'])) {
            echo json_encode([
                "success" => false,
                "message" => "Missing required field: content"
            ]);
            return;
        }

        $result = $this->comment->update($id, $input);

        if ($result) {
            echo json_encode([
                "success" => true,
                "message" => "Comment updated successfully"
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Failed to update comment or comment not found"
            ]);
        }
    }

    // Delete comment method
    public function delete() {
        header("Content-Type: application/json");
        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input || !isset($input['id'])) {
            echo json_encode([
                "success" => false,
                "message" => "Invalid JSON input or missing comment ID"
            ]);
            return;
        }

        $id = $input['id'];

        $result = $this->comment->delete($id);

        if ($result) {
            echo json_encode([
                "success" => true,
                "message" => "Comment deleted successfully"
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Failed to delete comment or comment not found"
            ]);
        }
    }

    // Get comments by user method
    public function getByUser() {
        header("Content-Type: application/json");

        $userId = $_GET['user_id'] ?? null;

        if (!$userId) {
            echo json_encode([
                "success" => false,
                "message" => "User ID is required"
            ]);
            return;
        }

        try {
            $comments = $this->comment->getByUser($userId);
            echo json_encode([
                "success" => true,
                "comments" => $comments
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    // Delete comment method using GET with ID parameter
    public function deleteById() {
        header("Content-Type: application/json");

        $commentId = $_GET['id'] ?? null;

        if (!$commentId) {
            echo json_encode([
                "success" => false,
                "message" => "Comment ID is required"
            ]);
            return;
        }

        // Check if the user is authorized to delete this comment
        // For testing purposes, we'll allow deletion if it matches current user context
        $userId = $_GET['user_id'] ?? null;

        try {
            // Get the comment to check ownership
            $comment = $this->comment->findById($commentId);
            if (!$comment) {
                echo json_encode([
                    "success" => false,
                    "message" => "Comment not found"
                ]);
                return;
            }

            // For testing purposes, allow deletion if user ID matches the comment owner
            // In a real system, this would check session authentication
            $isAuthorized = (!$userId) || ($userId == $comment['user_id']) ||
                           (isset($_GET['user_id_override']) && $_GET['user_id_override'] == '1'); // For admin override

            if (!$isAuthorized) {
                echo json_encode([
                    "success" => false,
                    "message" => "Unauthorized to delete this comment"
                ]);
                return;
            }

            $result = $this->comment->delete($commentId);

            if ($result) {
                echo json_encode([
                    "success" => true,
                    "message" => "Comment deleted successfully"
                ]);
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Failed to delete comment or comment not found"
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }
}
