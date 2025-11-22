<?php
// controllers/ActionParticipantController.php
// Action Participant Controller to handle business logic for joining actions

require_once "../config/config.php";

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

        // For testing purposes, we'll use a default user_id if not provided
        $actionId = $input['action_id'] ?? null;

        if (!$actionId) {
            error_log("Missing required field: action_id");
            echo json_encode([
                "success" => false,
                "message" => "Missing required field: action_id"
            ]);
            return;
        }

        // Use a default test user ID for testing purposes
        $userId = $input['user_id'] ?? 1; // Default to user ID 1 for testing

        // Check if user already joined this action
        $checkStmt = $this->pdo->prepare("SELECT id FROM action_participants WHERE action_id = :action_id AND user_id = :user_id");
        $checkStmt->execute([':action_id' => $actionId, ':user_id' => $userId]);

        try {
            if ($checkStmt->fetch()) {
                // User already joined, so remove them
                $deleteStmt = $this->pdo->prepare("DELETE FROM action_participants WHERE action_id = :action_id AND user_id = :user_id");
                $deleteStmt->execute([':action_id' => $actionId, ':user_id' => $userId]);

                echo json_encode([
                    "success" => true,
                    "message" => "Successfully left action",
                    "joined" => false
                ]);
            } else {
                // Add user to action participants
                $insertStmt = $this->pdo->prepare("INSERT INTO action_participants (action_id, user_id) VALUES (:action_id, :user_id)");
                $insertStmt->execute([':action_id' => $actionId, ':user_id' => $userId]);

                echo json_encode([
                    "success" => true,
                    "message" => "Successfully joined action",
                    "joined" => true
                ]);
            }
        } catch (Exception $e) {
            error_log("Error in joinAction: " . $e->getMessage());
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    // Get participants for an action
    public function getParticipants() {
        header("Content-Type: application/json");

        $actionId = $_GET['action_id'] ?? null;

        if (!$actionId) {
            echo json_encode([
                "success" => false,
                "message" => "Action ID is required"
            ]);
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

            echo json_encode([
                "success" => true,
                "participants" => $participants,
                "count" => count($participants)
            ]);
        } catch (Exception $e) {
            error_log("Error in getParticipants: " . $e->getMessage());
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    // Get participated actions for a user
    public function getByUser() {
        header("Content-Type: application/json");

        // Get user ID from query parameter for testing purposes
        $userId = $_GET['user_id'] ?? null;

        if (!$userId) {
            echo json_encode([
                "success" => false,
                "message" => "User ID is required"
            ]);
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

            echo json_encode([
                "success" => true,
                "actions" => $actions,
                "count" => count($actions)
            ]);
        } catch (Exception $e) {
            error_log("Error in getByUser: " . $e->getMessage());
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }
}