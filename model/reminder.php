<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/notification.php';

class Reminder {
    private $conn;
    private $notificationModel;

    public function __construct($pdo = null) {
        $this->conn = $pdo ?: Config::getConnexion();
        $this->notificationModel = new Notification($this->conn);
    }

    public function create($data) {
        try {
            // Validate that reminder time is in the future
            $reminderTime = new DateTime($data['reminder_time']);
            $now = new DateTime();

            if ($reminderTime <= $now) {
                return ['success' => false, 'message' => 'Reminder time must be in the future.'];
            }

            $query = "INSERT INTO reminders (user_id, item_id, item_type, reminder_type, reminder_time)
                      VALUES (:user_id, :item_id, :item_type, :reminder_type, :reminder_time)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $data['user_id']);
            $stmt->bindParam(':item_id', $data['item_id']);
            $stmt->bindParam(':item_type', $data['item_type']);
            $stmt->bindParam(':reminder_type', $data['reminder_type']);
            $stmt->bindParam(':reminder_time', $data['reminder_time']);

            if ($stmt->execute()) {
                // If this is an action reminder, also send notifications to all participants
                if ($data['item_type'] === 'action') {
                    // Get action details to use for notification
                    $actionQuery = "SELECT title, start_time FROM actions WHERE id = :action_id";
                    $actionStmt = $this->conn->prepare($actionQuery);
                    $actionStmt->bindParam(':action_id', $data['item_id']);
                    $actionStmt->execute();
                    $action = $actionStmt->fetch(PDO::FETCH_ASSOC);

                    if ($action) {
                        // Send notifications to all participants
                        $this->notificationModel->createActionReminderNotificationForAllParticipants(
                            $data['item_id'],
                            $action['title'],
                            $action['start_time']
                        );
                    }
                }

                return ['success' => true, 'message' => 'Reminder created successfully.'];
            } else {
                return ['success' => false, 'message' => 'Failed to create reminder.'];
            }
        } catch (PDOException $e) {
            error_log("Reminder creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            error_log("Reminder creation validation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Invalid reminder time format: ' . $e->getMessage()];
        }
    }

    public function getByUser($userId) {
        try {
            $query = "SELECT r.*,
                             COALESCE(a.title, rsc.resource_name) as item_title,
                             COALESCE(a.description, rsc.description) as item_description,
                             COALESCE(a.start_time, rsc.created_at) as item_datetime,
                             COALESCE(a.location, rsc.location) as item_location
                      FROM reminders r
                      LEFT JOIN actions a ON (r.item_id = a.id AND r.item_type = 'action')
                      LEFT JOIN resources rsc ON (r.item_id = rsc.id AND r.item_type = 'resource')
                      WHERE r.user_id = :user_id
                      ORDER BY r.reminder_time ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get reminders by user error: " . $e->getMessage());
            return [];
        }
    }

    public function getByItem($itemId, $itemType) {
        try {
            $query = "SELECT r.*, u.name as user_name, u.email as user_email
                      FROM reminders r
                      JOIN users u ON r.user_id = u.id
                      WHERE r.item_id = :item_id AND r.item_type = :item_type";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':item_id', $itemId);
            $stmt->bindParam(':item_type', $itemType);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get reminders by item error: " . $e->getMessage());
            return [];
        }
    }

    public function getDueReminders() {
        try {
            $now = date('Y-m-d H:i:s');
            $query = "SELECT r.*, u.name as user_name, u.email as user_email,
                             COALESCE(a.title, rsc.resource_name) as item_title,
                             COALESCE(a.description, rsc.description) as item_description,
                             COALESCE(a.start_time, rsc.created_at) as item_datetime,
                             COALESCE(a.location, rsc.location) as item_location
                      FROM reminders r
                      JOIN users u ON r.user_id = u.id
                      LEFT JOIN actions a ON (r.item_id = a.id AND r.item_type = 'action')
                      LEFT JOIN resources rsc ON (r.item_id = rsc.id AND r.item_type = 'resource')
                      WHERE r.reminder_time <= :now AND r.sent = 0";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':now', $now);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get due reminders error: " . $e->getMessage());
            return [];
        }
    }

    public function markAsSent($id) {
        try {
            $query = "UPDATE reminders SET sent = 1 WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Reminder marked as sent.'];
            } else {
                return ['success' => false, 'message' => 'Failed to mark reminder as sent.'];
            }
        } catch (PDOException $e) {
            error_log("Mark reminder as sent error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function delete($id) {
        try {
            $query = "DELETE FROM reminders WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Reminder deleted successfully.'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete reminder.'];
            }
        } catch (PDOException $e) {
            error_log("Delete reminder error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function deleteByItem($itemId, $itemType) {
        try {
            $query = "DELETE FROM reminders WHERE item_id = :item_id AND item_type = :item_type";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':item_id', $itemId);
            $stmt->bindParam(':item_type', $itemType);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Reminders for item deleted successfully.'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete reminders for item.'];
            }
        } catch (PDOException $e) {
            error_log("Delete reminders by item error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function createAutoReminder($userId, $itemId, $itemStartTime, $itemType = 'action') {
        try {
            // Calculate reminder time (24 hours before action start)
            $reminderDateTime = date('Y-m-d H:i:s', strtotime($itemStartTime . ' -24 hours'));
            $now = date('Y-m-d H:i:s');

            // Only create reminder if it's in the future
            if ($reminderDateTime > $now) {
                $data = [
                    'user_id' => $userId,
                    'item_id' => $itemId,
                    'item_type' => $itemType,
                    'reminder_type' => 'both', // Default to both email and in-app
                    'reminder_time' => $reminderDateTime
                ];

                return $this->create($data);
            } else {
                return ['success' => true, 'message' => 'Item is too soon for automatic reminder.'];
            }
        } catch (Exception $e) {
            error_log("Create auto reminder error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error creating auto reminder: ' . $e->getMessage()];
        }
    }

    public function getById($id) {
        try {
            $query = "SELECT * FROM reminders WHERE id = :id LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get reminder by ID error: " . $e->getMessage());
            return null;
        }
    }

    public function update($data) {
        try {
            // Validate that reminder time is in the future
            $reminderTime = new DateTime($data['reminder_time']);
            $now = new DateTime();

            if ($reminderTime <= $now) {
                return ['success' => false, 'message' => 'Reminder time must be in the future.'];
            }

            $query = "UPDATE reminders SET reminder_time = :reminder_time WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $data['id']);
            $stmt->bindParam(':reminder_time', $data['reminder_time']);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Reminder updated successfully.'];
            } else {
                return ['success' => false, 'message' => 'Failed to update reminder.'];
            }
        } catch (PDOException $e) {
            error_log("Update reminder error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            error_log("Update reminder validation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Invalid reminder time format: ' . $e->getMessage()];
        }
    }
}
?>