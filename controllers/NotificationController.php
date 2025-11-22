<?php
// controllers/NotificationController.php
// Notification Controller to handle business logic for user notifications

require_once "../config/config.php";
require_once "../model/notification.php";

class NotificationController
{
    private $pdo;
    private $notification;

    // Constructor initializes database connection and notification model
    public function __construct() {
        $this->pdo = Config::getConnexion();
        $this->notification = new Notification($this->pdo);
    }

    // Get notifications for a user method
    public function getByUser() {
        header("Content-Type: application/json");

        $userId = $_GET['user_id'] ?? null;
        $limit = (int)($_GET['limit'] ?? 10);

        if (!$userId) {
            echo json_encode([
                "success" => false,
                "message" => "User ID is required"
            ]);
            return;
        }

        try {
            $notifications = $this->notification->getByUser($userId, $limit);
            echo json_encode([
                "success" => true,
                "notifications" => $notifications
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    // Get all notifications method (for admin)
    public function getAll() {
        header("Content-Type: application/json");

        $limit = (int)($_GET['limit'] ?? 50);

        try {
            $notifications = $this->notification->getAll($limit);
            echo json_encode([
                "success" => true,
                "notifications" => $notifications
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    // Mark notification as read method
    public function markAsRead() {
        header("Content-Type: application/json");
        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input || !isset($input['id'])) {
            echo json_encode([
                "success" => false,
                "message" => "Invalid JSON input or missing notification ID"
            ]);
            return;
        }

        $id = $input['id'];

        $result = $this->notification->markAsRead($id);

        if ($result) {
            echo json_encode([
                "success" => true,
                "message" => "Notification marked as read"
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Failed to mark notification as read"
            ]);
        }
    }

    // Mark all notifications as read for user method
    public function markAllAsRead() {
        header("Content-Type: application/json");
        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input || !isset($input['user_id'])) {
            echo json_encode([
                "success" => false,
                "message" => "Invalid JSON input or missing user ID"
            ]);
            return;
        }

        $userId = $input['user_id'];

        $result = $this->notification->markAsReadForUser($userId);

        if ($result) {
            echo json_encode([
                "success" => true,
                "message" => "All notifications marked as read"
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Failed to mark notifications as read"
            ]);
        }
    }

    // Delete notification method
    public function delete() {
        header("Content-Type: application/json");
        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input || !isset($input['id'])) {
            echo json_encode([
                "success" => false,
                "message" => "Invalid JSON input or missing notification ID"
            ]);
            return;
        }

        $id = $input['id'];

        $result = $this->notification->delete($id);

        if ($result) {
            echo json_encode([
                "success" => true,
                "message" => "Notification deleted successfully"
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Failed to delete notification"
            ]);
        }
    }
}