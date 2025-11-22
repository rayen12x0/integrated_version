<?php
// models/Notification.php
// Notification model with PDO database operations for user notifications

class Notification
{
    private $pdo;

    // Constructor receives database connection
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Create a new notification in the database
    public function create($data) {
        error_log("Notification::create() called with data: " . print_r($data, true));
        // SQL query to insert new notification
        $sql = "INSERT INTO notifications (user_id, type, message, related_id)
                VALUES (:user_id, :type, :message, :related_id)";

        // Prepare the statement
        $stmt = $this->pdo->prepare($sql);

        // Execute with data provided
        $params = [
            ':user_id' => $data['user_id'] ?? null,
            ':type' => $data['type'] ?? null,
            ':message' => $data['message'] ?? '',
            ':related_id' => $data['related_id'] ?? null
        ];

        $success = $stmt->execute($params);
        if (!$success) {
            error_log("Notification::create() - SQL Error: " . print_r($stmt->errorInfo(), true));
        }
        return $success;
    }

    // Get notifications for a user
    public function getByUser($userId, $limit = 10) {
        $sql = "SELECT n.*, u.name as user_name, u.avatar_url as user_avatar,
                       a.title as action_title, r.resource_name as resource_name
                FROM notifications n
                LEFT JOIN users u ON n.user_id = u.id
                LEFT JOIN actions a ON n.related_id = a.id AND n.type LIKE '%action%'
                LEFT JOIN resources r ON n.related_id = r.id AND n.type LIKE '%resource%'
                WHERE n.user_id = :user_id
                ORDER BY n.created_at DESC
                LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format notifications for frontend
        foreach ($notifications as &$notification) {
            $notification['date'] = (new DateTime($notification['created_at']))->format('M d, Y H:i');
            $notification['is_new'] = !$notification['is_read'];
        }

        return $notifications;
    }

    // Get all notifications (for admin dashboard)
    public function getAll($limit = 50) {
        $sql = "SELECT n.*, u.name as user_name, u.avatar_url as user_avatar,
                       a.title as action_title, r.resource_name as resource_name
                FROM notifications n
                LEFT JOIN users u ON n.user_id = u.id
                LEFT JOIN actions a ON n.related_id = a.id AND n.type LIKE '%action%'
                LEFT JOIN resources r ON n.related_id = r.id AND n.type LIKE '%resource%'
                ORDER BY n.created_at DESC
                LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format notifications for frontend
        foreach ($notifications as &$notification) {
            $notification['date'] = (new DateTime($notification['created_at']))->format('M d, Y H:i');
            $notification['is_new'] = !$notification['is_read'];
        }

        return $notifications;
    }

    // Mark all notifications as read for a user
    public function markAsReadForUser($userId) {
        $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':user_id' => $userId]);
    }

    // Mark specific notification as read
    public function markAsRead($id) {
        $sql = "UPDATE notifications SET is_read = 1 WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    // Delete a notification
    public function delete($id) {
        $sql = "DELETE FROM notifications WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    // Create a notification for action approval
    public function createActionApprovedNotification($userId, $actionId, $actionTitle) {
        $data = [
            'user_id' => $userId,
            'type' => 'action_approved',
            'message' => "Your action '{$actionTitle}' has been approved.",
            'related_id' => $actionId
        ];
        return $this->create($data);
    }

    // Create a notification for action rejection
    public function createActionRejectedNotification($userId, $actionId, $actionTitle) {
        $data = [
            'user_id' => $userId,
            'type' => 'action_rejected',
            'message' => "Your action '{$actionTitle}' has been rejected.",
            'related_id' => $actionId
        ];
        return $this->create($data);
    }

    // Create a notification for resource approval
    public function createResourceApprovedNotification($userId, $resourceId, $resourceName) {
        $data = [
            'user_id' => $userId,
            'type' => 'resource_approved',
            'message' => "Your resource '{$resourceName}' has been approved.",
            'related_id' => $resourceId
        ];
        return $this->create($data);
    }

    // Create a notification for resource rejection
    public function createResourceRejectedNotification($userId, $resourceId, $resourceName) {
        $data = [
            'user_id' => $userId,
            'type' => 'resource_rejected',
            'message' => "Your resource '{$resourceName}' has been rejected.",
            'related_id' => $resourceId
        ];
        return $this->create($data);
    }
}