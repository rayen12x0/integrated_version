<?php
// controllers/ActionParticipantController.php
// Action Participant Controller to handle business logic for joining actions

require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../utils/AuthHelper.php";
require_once __DIR__ . "/../utils/ApiResponse.php";

class ActionParticipantController
{
    private $pdo;

    // Constructor initializes database connection
    public function __construct() {
        $this->pdo = Config::getConnexion();
    }

    // Join an action method
    public function joinAction() {
        error_log("ActionParticipantController::joinAction() called.");
        // Set JSON header
        header("Content-Type: application/json");

        try {
            // Get JSON input
            $input = json_decode(file_get_contents("php://input"), true);

            if ($input === null && json_last_error() !== JSON_ERROR_NONE) {
                error_log("Invalid JSON input received: " . json_last_error_msg());
                ApiResponse::error("Invalid JSON input received: " . json_last_error_msg(), 400);
                return;
            }

            error_log("Received input: " . print_r($input, true));

            // Validate input
            if (!$input) {
                error_log("No input received or empty input.");
                ApiResponse::error("No data received. Please ensure your request contains valid JSON data.", 400);
                return;
            }

            $actionId = $input['action_id'] ?? null;

            if (!$actionId) {
                error_log("Missing required field: action_id");
                ApiResponse::error("Action ID is required but not provided. Please check your request.", 400);
                return;
            }

            // Check if user is authenticated
            if (!AuthHelper::isLoggedIn()) {
                ApiResponse::error("You must be logged in to join actions. Please log in and try again.", 401);
                return;
            }

            $userId = $_SESSION['user_id'];

            // Check if action exists and is active
            $actionStmt = $this->pdo->prepare("SELECT id, status, title FROM actions WHERE id = :action_id");
            $actionStmt->execute([':action_id' => $actionId]);
            $action = $actionStmt->fetch(PDO::FETCH_ASSOC);

            if (!$action) {
                ApiResponse::error("The specified action does not exist. Please verify the action ID is correct.", 404);
                return;
            }

            if ($action['status'] === 'cancelled') {
                ApiResponse::error("This action has been cancelled and is no longer available for joining.", 400);
                return;
            }

            if ($action['status'] === 'completed') {
                ApiResponse::error("This action has already been completed and cannot be joined.", 400);
                return;
            }

            // Check if user already joined this action
            $checkStmt = $this->pdo->prepare("SELECT id FROM action_participants WHERE action_id = :action_id AND user_id = :user_id");
            $checkStmt->execute([':action_id' => $actionId, ':user_id' => $userId]);

            if ($checkStmt->fetch()) {
                // User already joined, so remove them
                $deleteStmt = $this->pdo->prepare("DELETE FROM action_participants WHERE action_id = :action_id AND user_id = :user_id");
                $deleteResult = $deleteStmt->execute([':action_id' => $actionId, ':user_id' => $userId]);

                if ($deleteResult) {
                    ApiResponse::success(['joined' => false], "You've left this action. Hope to see you at another event!", 200);
                } else {
                    error_log("Failed to remove user from action participants. Action ID: $actionId, User ID: $userId");
                    ApiResponse::error("Failed to leave the action. Please try again.", 400);
                }
            } else {
                // Add user to action participants
                $insertStmt = $this->pdo->prepare("INSERT INTO action_participants (action_id, user_id) VALUES (:action_id, :user_id)");
                $insertResult = $insertStmt->execute([':action_id' => $actionId, ':user_id' => $userId]);

                if ($insertResult) {
                    // Add notification for joining action
                    require_once __DIR__ . "/../model/notification.php";
                    $notification = new Notification($this->pdo);

                    // Get action creator ID and title
                    $actionStmt = $this->pdo->prepare("SELECT creator_id, title, start_time FROM actions WHERE id = :action_id");
                    $actionStmt->execute([':action_id' => $actionId]);
                    $action = $actionStmt->fetch(PDO::FETCH_ASSOC);

                    // Get user name
                    $userStmt = $this->pdo->prepare("SELECT name FROM users WHERE id = :user_id");
                    $userStmt->execute([':user_id' => $userId]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                    if ($action && $user) {
                        // Don't notify the user if they're joining their own action
                        if ($action['creator_id'] != $userId) {
                            $notification->createActionJoinedNotification($action['creator_id'], $actionId, $action['title'], $user['name']);
                        }

                        // Notify all other participants about the new joiner
                        $notification->createActionJoinedOtherParticipantsNotification($actionId, $userId, $action['title'], $user['name']);
                    }

                    ApiResponse::success(['joined' => true], "You've successfully joined this action!", 200);
                } else {
                    error_log("Failed to add user to action participants. Action ID: $actionId, User ID: $userId");
                    ApiResponse::error("Failed to join the action. Please try again.", 400);
                }
            }
        } catch (PDOException $e) {
            error_log("Database error in joinAction: " . $e->getMessage());
            ApiResponse::error("Database error occurred while processing your request: " . $e->getMessage() . ". Please try again later.", 500);
        } catch (Exception $e) {
            error_log("Error in joinAction: " . $e->getMessage());
            ApiResponse::error("An unexpected error occurred while processing your request: " . $e->getMessage() . ". Please try again later.", 500);
        }
    }

    // Get participants for an action
    public function getParticipants() {
        header("Content-Type: application/json");

        $actionId = $_GET['action_id'] ?? null;

        if (!$actionId) {
            ApiResponse::error("Action ID is required", 400);
            return;
        }

        try {
            // Get participants for the action with user details
            $stmt = $this->pdo->prepare("
                SELECT u.id, u.name, u.avatar_url, u.badge, ap.joined_at
                FROM action_participants ap
                JOIN users u ON ap.user_id = u.id
                WHERE ap.action_id = :action_id
                ORDER BY ap.joined_at DESC
            ");
            $stmt->execute([':action_id' => $actionId]);
            $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format participant data
            foreach ($participants as &$participant) {
                $participant['avatar_url'] = $participant['avatar_url'] ?: 'https://api.placeholder.com/40/40?text=' . strtoupper(substr($participant['name'], 0, 1));
                $participant['joined_at_formatted'] = date('M d, Y', strtotime($participant['joined_at']));
            }

            ApiResponse::success([
                "participants" => $participants,
                "count" => count($participants)
            ], 'Participants retrieved successfully', 200);
        } catch (Exception $e) {
            error_log("Error in getParticipants: " . $e->getMessage());
            ApiResponse::error("An error occurred while fetching participants. Please try again later.", 500);
        }
    }

    // Get participated actions for a user
    public function getByUser() {
        header("Content-Type: application/json");

        // Get user ID from query parameter for testing purposes
        $userId = $_GET['user_id'] ?? null;

        if (!$userId) {
            ApiResponse::error("User ID is required", 400);
            return;
        }

        try {
            // Get actions that the user has participated in with action details
            $stmt = $this->pdo->prepare("
                SELECT a.*, u.name as creator_name, u.avatar_url as creator_avatar, u.badge as creator_badge,
                       ap.joined_at as participation_date
                FROM action_participants ap
                JOIN actions a ON ap.action_id = a.id
                LEFT JOIN users u ON a.creator_id = u.id
                WHERE ap.user_id = :user_id
                ORDER BY ap.joined_at DESC
            ");
            $stmt->execute([':user_id' => $userId]);
            $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format action data for frontend
            foreach ($actions as &$action) {
                // Use actual image from DB if available, otherwise use default
                $action['image'] = $action['image_url'] ?: 'https://via.placeholder.com/400x200?text=Action+Image';

                // Format creator info
                $action['creator'] = [
                    'name' => $action['creator_name'] ?: 'Unknown Creator',
                    'avatar' => $action['creator_avatar'] ?: 'https://api.placeholder.com/40/40?text=' . strtoupper(substr($action['creator_name'] ?? 'U', 0, 1)),
                    'badge' => $action['creator_badge'] ?: 'Community Member'
                ];

                // Get participant count for this action
                $participantStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM action_participants WHERE action_id = :action_id");
                $participantStmt->execute([':action_id' => $action['id']]);
                $participantCount = $participantStmt->fetch(PDO::FETCH_ASSOC);
                $action['participants'] = $participantCount['count'] ?? 0;

                // Get comment count for this action
                $commentStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE action_id = :action_id");
                $commentStmt->execute([':action_id' => $action['id']]);
                $commentCount = $commentStmt->fetch(PDO::FETCH_ASSOC);
                $action['comment_count'] = $commentCount['count'] ?? 0;

                // Format date for display
                $actionDate = $action['start_time'] ? new DateTime($action['start_time']) : null;
                $action['date'] = $actionDate ? $actionDate->format('M d, Y H:i') : 'Date not specified';

                // Calculate duration
                if ($action['start_time'] && $action['end_time']) {
                    $start = new DateTime($action['start_time']);
                    $end = new DateTime($action['end_time']);
                    $interval = $end->diff($start);
                    $action['duration'] = $interval->h . ' hours ' . $interval->i . ' minutes';
                } else {
                    $action['duration'] = 'N/A';
                }

                // Set tags to empty array for now (can be extended later)
                $action['tags'] = [];
            }

            ApiResponse::success([
                "actions" => $actions,
                "count" => count($actions)
            ], 'Participated actions retrieved successfully', 200);
        } catch (Exception $e) {
            error_log("Error in getByUser: " . $e->getMessage());
            ApiResponse::error("An error occurred while fetching your participated actions. Please try again later.", 500);
        }
    }

}