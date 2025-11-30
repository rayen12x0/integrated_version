<?php
// models/Notification.php
// Notification model with PDO database operations for user notifications
require_once __DIR__ . '/../utils/EmailService.php';

class Notification
{
    private $pdo;

    // Constructor receives database connection
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Helper method to get user email by user ID
    private function getUserEmail($userId) {
        $sql = "SELECT email FROM users WHERE id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['email'] : null;
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
            return $success;
        }

        // Send email notification based on notification type
        if (isset($data['user_id']) && $data['user_id']) {
            $userEmail = $this->getUserEmail($data['user_id']);
            $userName = $this->getUserName($data['user_id']);

            if ($userEmail && $userName) {
                switch ($data['type']) {
                    case 'action_approved':
                        EmailService::sendNotificationEmail($userEmail, $userName, "Your Action Has Been Approved", $data['message'], null, null);
                        break;
                    case 'action_rejected':
                        EmailService::sendNotificationEmail($userEmail, $userName, "Your Action Has Been Rejected", $data['message'], null, null);
                        break;
                    case 'resource_approved':
                        EmailService::sendNotificationEmail($userEmail, $userName, "Your Resource Has Been Approved", $data['message'], null, null);
                        break;
                    case 'resource_rejected':
                        EmailService::sendNotificationEmail($userEmail, $userName, "Your Resource Has Been Rejected", $data['message'], null, null);
                        break;
                    case 'action_comment_added':
                    case 'resource_comment_added':
                        $itemType = strpos($data['type'], 'action') === 0 ? 'action' : 'resource';
                        EmailService::sendNotificationEmail($userEmail, $userName, "New Comment on Your {$itemType}", $data['message'], null, null);
                        break;
                }
            }
        }

        return $success;
    }

    // Helper method to get user name by user ID
    private function getUserName($userId) {
        $sql = "SELECT name FROM users WHERE id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['name'] : null;
    }

    // Get notifications for a user
    public function getByUser($userId, $limit = 10) {
        $sql = "SELECT n.*, u.name as user_name, u.avatar_url as user_avatar,
                       a.title as action_title, r.resource_name as resource_name, rep.report_category as report_category
                FROM notifications n
                LEFT JOIN users u ON n.user_id = u.id
                LEFT JOIN actions a ON n.related_id = a.id AND (n.type LIKE 'action\\_%' OR n.type = 'action_approved' OR n.type = 'action_rejected' OR n.type = 'action_created' OR n.type = 'action_updated' OR n.type = 'action_deleted' OR n.type = 'action_joined')
                LEFT JOIN resources r ON n.related_id = r.id AND (n.type LIKE 'resource\\_%' OR n.type = 'resource_approved' OR n.type = 'resource_rejected' OR n.type = 'resource_created' OR n.type = 'resource_updated' OR n.type = 'resource_deleted')
                LEFT JOIN reports rep ON n.related_id = rep.id AND n.type = 'report_created'
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
                       a.title as action_title, r.resource_name as resource_name, rep.report_category as report_category
                FROM notifications n
                LEFT JOIN users u ON n.user_id = u.id
                LEFT JOIN actions a ON n.related_id = a.id AND (n.type LIKE 'action\\_%' OR n.type = 'action_approved' OR n.type = 'action_rejected' OR n.type = 'action_created' OR n.type = 'action_updated' OR n.type = 'action_deleted' OR n.type = 'action_joined')
                LEFT JOIN resources r ON n.related_id = r.id AND (n.type LIKE 'resource\\_%' OR n.type = 'resource_approved' OR n.type = 'resource_rejected' OR n.type = 'resource_created' OR n.type = 'resource_updated' OR n.type = 'resource_deleted')
                LEFT JOIN reports rep ON n.related_id = rep.id AND n.type = 'report_created'
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

    // Action notifications
    public function createActionCreatedNotification($userId, $actionId, $actionTitle) {
        $data = [
            'user_id' => $userId,
            'type' => 'action_created',
            'message' => "Your action '{$actionTitle}' has been created successfully and is pending approval.",
            'related_id' => $actionId
        ];
        return $this->create($data);
    }

    public function createActionUpdatedNotification($userId, $actionId, $actionTitle) {
        $data = [
            'user_id' => $userId,
            'type' => 'action_updated',
            'message' => "Your action '{$actionTitle}' has been updated successfully.",
            'related_id' => $actionId
        ];
        return $this->create($data);
    }

    public function createActionDeletedNotification($userId, $actionTitle) {
        $data = [
            'user_id' => $userId,
            'type' => 'action_deleted',
            'message' => "Your action '{$actionTitle}' has been deleted successfully.",
            'related_id' => null
        ];
        return $this->create($data);
    }

    public function createActionJoinedNotification($creatorId, $actionId, $actionTitle, $participantName) {
        $data = [
            'user_id' => $creatorId,
            'type' => 'action_joined',
            'message' => "{$participantName} has joined your action '{$actionTitle}'.",
            'related_id' => $actionId
        ];
        return $this->create($data);
    }

    // Resource notifications
    public function createResourceCreatedNotification($userId, $resourceId, $resourceName) {
        $data = [
            'user_id' => $userId,
            'type' => 'resource_created',
            'message' => "Your resource '{$resourceName}' has been created successfully and is pending approval.",
            'related_id' => $resourceId
        ];
        return $this->create($data);
    }

    public function createResourceUpdatedNotification($userId, $resourceId, $resourceName) {
        $data = [
            'user_id' => $userId,
            'type' => 'resource_updated',
            'message' => "Your resource '{$resourceName}' has been updated successfully.",
            'related_id' => $resourceId
        ];
        return $this->create($data);
    }

    public function createResourceDeletedNotification($userId, $resourceName) {
        $data = [
            'user_id' => $userId,
            'type' => 'resource_deleted',
            'message' => "Your resource '{$resourceName}' has been deleted successfully.",
            'related_id' => null
        ];
        return $this->create($data);
    }

    // Comment notifications
    public function createCommentAddedNotification($creatorId, $itemId, $itemTitle, $commenterName, $itemType) {
        $itemTypeLabel = $itemType === 'action' ? 'action' : 'resource';
        $type = $itemType . '_comment_added'; // Encode item type in notification type
        $data = [
            'user_id' => $creatorId,
            'type' => $type,
            'message' => "{$commenterName} commented on your {$itemTypeLabel} '{$itemTitle}'.",
            'related_id' => $itemId
        ];
        return $this->create($data);
    }

    /**
     * Create a reminder notification for a user
     */
    public function createReminderNotification($userId, $itemId, $itemType, $itemTitle, $itemDate) {
        $formattedDate = date('F j, Y \a\t g:i A', strtotime($itemDate));
        $data = [
            'user_id' => $userId,
            'type' => 'reminder',
            'message' => "Reminder: Your {$itemType} '{$itemTitle}' is scheduled for {$formattedDate}",
            'related_id' => $itemId
        ];
        return $this->create($data);
    }
}