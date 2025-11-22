<?php
// models/Action.php
// Enhanced Action model with PDO database operations and real user/creator integration

class Action
{
    private $pdo;

    // Constructor receives database connection
    public function __construct($pdo = null) {
        if ($pdo === null) {
            // Check if Config class is available, if not, include config
            if (!class_exists('Config')) {
                require_once __DIR__ . '/../config/config.php';
            }
            $this->pdo = Config::getConnexion();
        } else {
            $this->pdo = $pdo;
        }
    }

    // Create a new action in the database
    public function create($data) {
        error_log("Action::create() called with data: " . print_r($data, true));
        // SQL query to insert new action
        $sql = "INSERT INTO actions (creator_id, title, description, category, theme, location, country, latitude, longitude, start_time, end_time, status, image_url)
                VALUES (:creator_id, :title, :description, :category, :theme, :location, :country, :latitude, :longitude, :start_time, :end_time, :status, :image_url)";

        // Prepare the statement
        $stmt = $this->pdo->prepare($sql);

        // Map the data to only include fields in the query and set defaults
        $params = [
            ':creator_id' => $data['creator_id'] ?? null,
            ':title' => $data['title'] ?? null,
            ':description' => $data['description'] ?? null,
            ':category' => $data['category'] ?? null,
            ':theme' => $data['theme'] ?? null,
            ':location' => $data['location'] ?? null,
            ':country' => $data['country'] ?? null,
            ':latitude' => $data['latitude'] ?? null,
            ':longitude' => $data['longitude'] ?? null,
            ':start_time' => $data['start_time'] ?? null,
            ':end_time' => $data['end_time'] ?? null,
            ':status' => $data['status'] ?? 'pending',
            ':image_url' => $data['image_url'] ?? 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEFjdGlvbiBJbWFnZTwvdGV4dD48L3N2Zz4='
        ];

        // Execute with proper parameters
        $success = $stmt->execute($params);
        if (!$success) {
            error_log("Action::create() - SQL Error: " . print_r($stmt->errorInfo(), true));
        }

        // If creation was successful, create a notification for admin
        if ($success) {
            $this->createActionCreatedNotification($data['creator_id'], $this->pdo->lastInsertId(), $data['title'] ?? 'Untitled Action');
        }

        return $success;
    }

    // Get all actions from database and enrich with actual data for frontend
    public function getAll() {
        $sql = "SELECT a.*, u.name as creator_name, u.avatar_url as creator_avatar, u.badge as creator_badge
                FROM actions a
                LEFT JOIN users u ON a.creator_id = u.id
                ORDER BY a.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enrich data with actual counts and real values for frontend
        foreach ($actions as &$action) {
            // Use actual image from DB if available, otherwise use default
            $action['image'] = $action['image_url'] ?? 'https://via.placeholder.com/400x200?text=Action+Image'; // Default image

            // Format creator info
            $action['creator'] = [
                'name' => $action['creator_name'] ?? 'Unknown Creator',
                'avatar' => $action['creator_avatar'] ?: 'https://api.placeholder.com/40/40?text=' . strtoupper(substr($action['creator_name'] ?? 'U', 0, 1)),
                'badge' => $action['creator_badge'] ?: 'Community Member'
            ];

            // Get actual participant count
            $participantStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM action_participants WHERE action_id = :id");
            $participantStmt->execute([':id' => $action['id']]);
            $participantCount = $participantStmt->fetch(PDO::FETCH_ASSOC);
            $action['participants'] = $participantCount['count'];

            // Get actual comment count
            $commentStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE action_id = :id");
            $commentStmt->execute([':id' => $action['id']]);
            $commentCount = $commentStmt->fetch(PDO::FETCH_ASSOC);
            $action['comment_count'] = $commentCount['count'];

            // Format date for display
            if (isset($action['start_time'])) {
                $action['date'] = (new DateTime($action['start_time']))->format('M d, Y H:i');
            } else {
                $action['date'] = 'N/A';
            }

            // Calculate duration
            if (isset($action['start_time']) && isset($action['end_time'])) {
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
        return $actions;
    }

    // Get actions by creator ID (for user dashboard)
    public function getByCreatorId($creatorId) {
        $sql = "SELECT a.*, u.name as creator_name, u.avatar_url as creator_avatar, u.badge as creator_badge
                FROM actions a
                LEFT JOIN users u ON a.creator_id = u.id
                WHERE a.creator_id = :creator_id
                ORDER BY a.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':creator_id' => $creatorId]);
        $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enrich data with actual counts and real values for frontend
        foreach ($actions as &$action) {
            // Use actual image from DB if available, otherwise use default
            $action['image'] = $action['image_url'] ?? 'https://via.placeholder.com/400x200?text=Action+Image'; // Default image

            // Format creator info
            $action['creator'] = [
                'name' => $action['creator_name'] ?? 'Unknown Creator',
                'avatar' => $action['creator_avatar'] ?: 'https://api.placeholder.com/40/40?text=' . strtoupper(substr($action['creator_name'] ?? 'U', 0, 1)),
                'badge' => $action['creator_badge'] ?: 'Community Member'
            ];

            // Get actual participant count
            $participantStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM action_participants WHERE action_id = :id");
            $participantStmt->execute([':id' => $action['id']]);
            $participantCount = $participantStmt->fetch(PDO::FETCH_ASSOC);
            $action['participants'] = $participantCount['count'];

            // Get actual comment count
            $commentStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE action_id = :id");
            $commentStmt->execute([':id' => $action['id']]);
            $commentCount = $commentStmt->fetch(PDO::FETCH_ASSOC);
            $action['comment_count'] = $commentCount['count'];

            // Format date for display
            if (isset($action['start_time'])) {
                $action['date'] = (new DateTime($action['start_time']))->format('M d, Y H:i');
            } else {
                $action['date'] = 'N/A';
            }

            // Calculate duration
            if (isset($action['start_time']) && isset($action['end_time'])) {
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
        return $actions;
    }

    // Find action by ID
    public function findById($id) {
        $sql = "SELECT a.*, u.name as creator_name, u.avatar_url as creator_avatar, u.badge as creator_badge
                FROM actions a
                LEFT JOIN users u ON a.creator_id = u.id
                WHERE a.id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $action = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($action) {
            // Use actual image from DB if available, otherwise use default
            $action['image'] = $action['image_url'] ?? 'https://via.placeholder.com/400x200?text=Action+Image'; // Default image

            // Format creator info
            $action['creator'] = [
                'name' => $action['creator_name'] ?? 'Unknown Creator',
                'avatar' => $action['creator_avatar'] ?: 'https://api.placeholder.com/40/40?text=' . strtoupper(substr($action['creator_name'] ?? 'U', 0, 1)),
                'badge' => $action['creator_badge'] ?: 'Community Member'
            ];

            // Get actual participant count
            $participantStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM action_participants WHERE action_id = :id");
            $participantStmt->execute([':id' => $id]);
            $participantCount = $participantStmt->fetch(PDO::FETCH_ASSOC);
            $action['participants'] = $participantCount['count'];

            // Get actual comment count
            $commentStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE action_id = :id");
            $commentStmt->execute([':id' => $id]);
            $commentCount = $commentStmt->fetch(PDO::FETCH_ASSOC);
            $action['comment_count'] = $commentCount['count'];

            // Format date for display
            if (isset($action['start_time'])) {
                $action['date'] = (new DateTime($action['start_time']))->format('M d, Y H:i');
            } else {
                $action['date'] = 'N/A';
            }

            // Calculate duration
            if (isset($action['start_time']) && isset($action['end_time'])) {
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

        return $action;
    }

    // Update an action
    public function update($id, $data) {
        error_log("Action::update() called for ID: " . $id . " with data: " . print_r($data, true));

        // Get the current action to access the old image URL
        $currentAction = $this->findById($id);
        $oldImageUrl = $currentAction ? $currentAction['image_url'] : null;

        $sql = "UPDATE actions SET";
        $params = [];
        $sqlParts = [];

        // Only include fields that are provided in the data
        if (isset($data['title'])) {
            $sqlParts[] = "title = :title";
            $params[':title'] = $data['title'];
        }
        if (isset($data['description'])) {
            $sqlParts[] = "description = :description";
            $params[':description'] = $data['description'];
        }
        if (isset($data['category'])) {
            $sqlParts[] = "category = :category";
            $params[':category'] = $data['category'];
        }
        if (isset($data['theme'])) {
            $sqlParts[] = "theme = :theme";
            $params[':theme'] = $data['theme'];
        }
        if (isset($data['location'])) {
            $sqlParts[] = "location = :location";
            $params[':location'] = $data['location'];
        }
        if (isset($data['latitude'])) {
            $sqlParts[] = "latitude = :latitude";
            $params[':latitude'] = $data['latitude'];
        }
        if (isset($data['longitude'])) {
            $sqlParts[] = "longitude = :longitude";
            $params[':longitude'] = $data['longitude'];
        }
        if (isset($data['start_time'])) {
            $sqlParts[] = "start_time = :start_time";
            $params[':start_time'] = $data['start_time'];
        }
        if (isset($data['end_time'])) {
            $sqlParts[] = "end_time = :end_time";
            $params[':end_time'] = $data['end_time'];
        }
        if (isset($data['status'])) {
            $sqlParts[] = "status = :status";
            $params[':status'] = $data['status'];
        }
        if (isset($data['image_url'])) {
            $sqlParts[] = "image_url = :image_url";
            $params[':image_url'] = $data['image_url'];
        }

        $sql .= " " . implode(", ", $sqlParts) . " WHERE id = :id";
        $params[':id'] = $id;

        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute($params);
        if (!$success) {
            error_log("Action::update() - SQL Error: " . print_r($stmt->errorInfo(), true));
        }

        // Delete old image if a new one was uploaded and the old one exists
        if ($success && isset($data['image_url']) && !empty($oldImageUrl) && $oldImageUrl !== $data['image_url']) {
            require_once '../utils/imageUpload.php';
            ImageUpload::deleteImage($oldImageUrl);
        }

        return $success;
    }

    // Delete an action
    public function delete($id) {
        // Get the action to access the image URL before deletion
        $action = $this->findById($id);
        $imageUrl = $action ? $action['image_url'] : null;

        // First delete related comments and participants
        $deleteCommentsStmt = $this->pdo->prepare("DELETE FROM comments WHERE action_id = :id");
        $deleteCommentsStmt->execute([':id' => $id]);

        $deleteParticipantsStmt = $this->pdo->prepare("DELETE FROM action_participants WHERE action_id = :id");
        $deleteParticipantsStmt->execute([':id' => $id]);

        // Then delete the action itself
        $sql = "DELETE FROM actions WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([':id' => $id]);

        // Delete the associated image file if it exists
        if ($success && !empty($imageUrl)) {
            require_once '../utils/imageUpload.php';
            ImageUpload::deleteImage($imageUrl);
        }

        return $success;
    }

    // Approve an action
    public function approve($id) {
        // First get the action details to use for notification
        $action = $this->findById($id);
        if (!$action) {
            return false;
        }

        $sql = "UPDATE actions SET status = 'approved' WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([':id' => $id]);

        // Create notification if approval was successful
        if ($result) {
            // Include the notification functionality
            require_once '../model/notification.php';
            $notification = new Notification($this->pdo);
            $notification->createActionApprovedNotification($action['creator_id'], $id, $action['title']);
        }

        return $result;
    }

    // Reject an action
    public function reject($id) {
        // First get the action details to use for notification
        $action = $this->findById($id);
        if (!$action) {
            return false;
        }

        $sql = "UPDATE actions SET status = 'rejected' WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([':id' => $id]);

        // Create notification if rejection was successful
        if ($result) {
            // Include the notification functionality
            require_once '../model/notification.php';
            $notification = new Notification($this->pdo);
            $notification->createActionRejectedNotification($action['creator_id'], $id, $action['title']);
        }

        return $result;
    }

    // Get pending actions (for admin approval)
    public function getPending() {
        $sql = "SELECT a.*, u.name as creator_name, u.avatar_url as creator_avatar, u.badge as creator_badge
                FROM actions a
                LEFT JOIN users u ON a.creator_id = u.id
                WHERE a.status = 'pending'
                ORDER BY a.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enrich data with actual counts and real values for frontend
        foreach ($actions as &$action) {
            // Use actual image from DB if available, otherwise use default
            $action['image'] = $action['image_url'] ?? 'https://via.placeholder.com/400x200?text=Action+Image'; // Default image

            // Format creator info
            $action['creator'] = [
                'name' => $action['creator_name'] ?? 'Unknown Creator',
                'avatar' => $action['creator_avatar'] ?: 'https://api.placeholder.com/40/40?text=' . strtoupper(substr($action['creator_name'] ?? 'U', 0, 1)),
                'badge' => $action['creator_badge'] ?: 'Community Member'
            ];

            // Get actual participant count
            $participantStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM action_participants WHERE action_id = :id");
            $participantStmt->execute([':id' => $action['id']]);
            $participantCount = $participantStmt->fetch(PDO::FETCH_ASSOC);
            $action['participants'] = $participantCount['count'];

            // Get actual comment count
            $commentStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE action_id = :id");
            $commentStmt->execute([':id' => $action['id']]);
            $commentCount = $commentStmt->fetch(PDO::FETCH_ASSOC);
            $action['comment_count'] = $commentCount['count'];

            // Format date for display
            if (isset($action['start_time'])) {
                $action['date'] = (new DateTime($action['start_time']))->format('M d, Y H:i');
            } else {
                $action['date'] = 'N/A';
            }

            // Calculate duration
            if (isset($action['start_time']) && isset($action['end_time'])) {
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
        return $actions;
    }

    // Get approved actions (for public display)
    public function getApproved() {
        $sql = "SELECT a.*, u.name as creator_name, u.avatar_url as creator_avatar, u.badge as creator_badge
                FROM actions a
                LEFT JOIN users u ON a.creator_id = u.id
                WHERE a.status = 'approved'
                ORDER BY a.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enrich data with actual counts and real values for frontend
        foreach ($actions as &$action) {
            // Use actual image from DB if available, otherwise use default
            $action['image'] = $action['image_url'] ?? 'https://via.placeholder.com/400x200?text=Action+Image'; // Default image

            // Format creator info
            $action['creator'] = [
                'name' => $action['creator_name'] ?? 'Unknown Creator',
                'avatar' => $action['creator_avatar'] ?: 'https://api.placeholder.com/40/40?text=' . strtoupper(substr($action['creator_name'] ?? 'U', 0, 1)),
                'badge' => $action['creator_badge'] ?: 'Community Member'
            ];

            // Get actual participant count
            $participantStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM action_participants WHERE action_id = :id");
            $participantStmt->execute([':id' => $action['id']]);
            $participantCount = $participantStmt->fetch(PDO::FETCH_ASSOC);
            $action['participants'] = $participantCount['count'];

            // Get actual comment count
            $commentStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE action_id = :id");
            $commentStmt->execute([':id' => $action['id']]);
            $commentCount = $commentStmt->fetch(PDO::FETCH_ASSOC);
            $action['comment_count'] = $commentCount['count'];

            // Format date for display
            if (isset($action['start_time'])) {
                $action['date'] = (new DateTime($action['start_time']))->format('M d, Y H:i');
            } else {
                $action['date'] = 'N/A';
            }

            // Calculate duration
            if (isset($action['start_time']) && isset($action['end_time'])) {
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
        return $actions;
    }

    // Get actions by status
    public function getByStatus($status) {
        $sql = "SELECT a.*, u.name as creator_name, u.avatar_url as creator_avatar, u.badge as creator_badge
                FROM actions a
                LEFT JOIN users u ON a.creator_id = u.id
                WHERE a.status = :status
                ORDER BY a.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':status' => $status]);
        $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enrich data with actual counts and real values for frontend
        foreach ($actions as &$action) {
            // Use actual image from DB if available, otherwise use default
            $action['image'] = $action['image_url'] ?? 'https://via.placeholder.com/400x200?text=Action+Image'; // Default image

            // Format creator info
            $action['creator'] = [
                'name' => $action['creator_name'] ?? 'Unknown Creator',
                'avatar' => $action['creator_avatar'] ?: 'https://api.placeholder.com/40/40?text=' . strtoupper(substr($action['creator_name'] ?? 'U', 0, 1)),
                'badge' => $action['creator_badge'] ?: 'Community Member'
            ];

            // Get actual participant count
            $participantStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM action_participants WHERE action_id = :id");
            $participantStmt->execute([':id' => $action['id']]);
            $participantCount = $participantStmt->fetch(PDO::FETCH_ASSOC);
            $action['participants'] = $participantCount['count'];

            // Get actual comment count
            $commentStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE action_id = :id");
            $commentStmt->execute([':id' => $action['id']]);
            $commentCount = $commentStmt->fetch(PDO::FETCH_ASSOC);
            $action['comment_count'] = $commentCount['count'];

            // Format date for display
            if (isset($action['start_time'])) {
                $action['date'] = (new DateTime($action['start_time']))->format('M d, Y H:i');
            } else {
                $action['date'] = 'N/A';
            }

            // Calculate duration
            if (isset($action['start_time']) && isset($action['end_time'])) {
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
        return $actions;
    }

    // Get actions by country
    public function getByCountry($country) {
        try {
            $sql = "SELECT a.*, u.name as creator_name, u.avatar_url as creator_avatar, u.badge as creator_badge
                    FROM actions a
                    LEFT JOIN users u ON a.creator_id = u.id
                    WHERE (a.country = :country OR LOWER(a.country) = LOWER(:country))
                    AND a.status = 'approved'
                    ORDER BY a.created_at DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':country', $country, PDO::PARAM_STR);
            $stmt->execute();

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Process each action to enrich with additional information
            foreach ($results as &$action) {
                // Use actual image from DB if available, otherwise use default
                $action['image'] = $action['image_url'] ?? 'https://via.placeholder.com/400x200?text=Action+Image';

                // Format creator info
                $action['creator'] = [
                    'name' => $action['creator_name'] ?? 'Unknown Creator',
                    'avatar' => $action['creator_avatar'] ?: 'https://api.placeholder.com/40/40?text=' . strtoupper(substr($action['creator_name'] ?? 'U', 0, 1)),
                    'badge' => $action['creator_badge'] ?: 'Community Member'
                ];

                // Get actual participant count
                $participantStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM action_participants WHERE action_id = :id");
                $participantStmt->execute([':id' => $action['id']]);
                $participantCount = $participantStmt->fetch(PDO::FETCH_ASSOC);
                $action['participants'] = (int)($participantCount['count'] ?? 0);

                // Get actual comment count
                $commentStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE action_id = :id");
                $commentStmt->execute([':id' => $action['id']]);
                $commentCount = $commentStmt->fetch(PDO::FETCH_ASSOC);
                $action['comment_count'] = (int)($commentCount['count'] ?? 0);

                // Format date for display
                if (isset($action['start_time'])) {
                    $action['date'] = (new DateTime($action['start_time']))->format('M d, Y H:i');
                } else {
                    $action['date'] = 'N/A';
                }

                // Calculate duration
                if (isset($action['start_time']) && isset($action['end_time'])) {
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

            return $results;
        } catch (PDOException $e) {
            error_log("Error getting actions by country: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    // Create a notification for when an action is created
    private function createActionCreatedNotification($userId, $actionId, $actionTitle) {
        try {
            // Find admin users to send the notification to
            $adminStmt = $this->pdo->prepare("SELECT id FROM users WHERE role = 'admin'");
            $adminStmt->execute();
            $adminUsers = $adminStmt->fetchAll(PDO::FETCH_COLUMN);

            // Create notification for each admin
            foreach ($adminUsers as $adminId) {
                $notificationSql = "INSERT INTO notifications (user_id, type, message, related_id)
                                   VALUES (:user_id, :type, :message, :related_id)";
                $notificationStmt = $this->pdo->prepare($notificationSql);

                $notificationParams = [
                    ':user_id' => $adminId,
                    ':type' => 'action_created',
                    ':message' => "New action '{$actionTitle}' has been submitted for approval by user ID {$userId}.",
                    ':related_id' => $actionId
                ];

                $notificationStmt->execute($notificationParams);
            }
        } catch (Exception $e) {
            error_log("Error creating action created notification: " . $e->getMessage());
        }
    }

}