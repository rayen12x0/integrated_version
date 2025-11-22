<?php
// models/Resource.php
// Enhanced Resource model with PDO database operations and real user integration

class Resource
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

    // Create a new resource in the database
    public function create($data) {
        error_log("Resource::create() called with data: " . print_r($data, true));
        // SQL query to insert new resource
        $sql = "INSERT INTO resources (publisher_id, resource_name, description, type, category, location, country, latitude, longitude, status, image_url)
                VALUES (:publisher_id, :resource_name, :description, :type, :category, :location, :country, :latitude, :longitude, :status, :image_url)";

        // Prepare the statement
        $stmt = $this->pdo->prepare($sql);

        // Map the data to only include fields in the query and set defaults
        $params = [
            ':publisher_id' => $data['publisher_id'] ?? null,
            ':resource_name' => $data['resource_name'] ?? null,
            ':description' => $data['description'] ?? null,
            ':type' => $data['type'] ?? null,
            ':category' => $data['category'] ?? null,
            ':location' => $data['location'] ?? null,
            ':country' => $data['country'] ?? null,
            ':latitude' => $data['latitude'] ?? null,
            ':longitude' => $data['longitude'] ?? null,
            ':status' => $data['status'] ?? 'pending',
            ':image_url' => $data['image_url'] ?? 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIFJlc291cmNlIEltYWdlPC90ZXh0Pjwvc3ZnPg=='
        ];

        // Execute with proper parameters
        $success = $stmt->execute($params);
        if (!$success) {
            error_log("Resource::create() - SQL Error: " . print_r($stmt->errorInfo(), true));
        }

        // If creation was successful, create a notification for admin
        if ($success) {
            $this->createResourceCreatedNotification($data['publisher_id'], $this->pdo->lastInsertId(), $data['resource_name'] ?? 'Untitled Resource');
        }

        return $success;
    }

    // Get all resources from database and enrich with actual data for frontend
    public function getAll() {
        $sql = "SELECT r.*, u.name as publisher_name, u.avatar_url as publisher_avatar, u.badge as publisher_badge
                FROM resources r
                LEFT JOIN users u ON r.publisher_id = u.id
                ORDER BY r.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enrich data with actual counts and real values for frontend
        foreach ($resources as &$resource) {
            // Use actual image from DB if available, otherwise use default
            $resource['image'] = $resource['image_url'] ?? 'https://via.placeholder.com/400x200?text=Resource+Image'; // Default image

            // Format publisher info
            $resource['creator'] = [
                'name' => $resource['publisher_name'] ?? 'Unknown Publisher',
                'avatar' => $resource['publisher_avatar'] ?: 'https://api.placeholder.com/40/40?text=' . strtoupper(substr($resource['publisher_name'] ?? 'U', 0, 1)),
                'badge' => $resource['publisher_badge'] ?: 'Community Member'
            ];

            // Resources don't typically have participants like actions, but they may have responders
            $resource['participants'] = 0; // Could be expanded to track who has responded to the resource

            // Get actual comment count for this resource
            $commentStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE resource_id = :id");
            $commentStmt->execute([':id' => $resource['id']]);
            $commentCount = $commentStmt->fetch(PDO::FETCH_ASSOC);
            $resource['comment_count'] = $commentCount['count'];

            // Resources don't have start_time or end_time
            $resource['date'] = (new DateTime($resource['created_at']))->format('M d, Y H:i'); // Use created_at as date
            $resource['duration'] = 'N/A'; // Resources don't have duration

            // Set tags to empty array for now (can be extended later)
            $resource['tags'] = [];
        }
        return $resources;
    }

    // Get resources by publisher ID (for user dashboard)
    public function getByPublisherId($publisherId) {
        $sql = "SELECT r.*, u.name as publisher_name, u.avatar_url as publisher_avatar, u.badge as publisher_badge
                FROM resources r
                LEFT JOIN users u ON r.publisher_id = u.id
                WHERE r.publisher_id = :publisher_id
                ORDER BY r.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':publisher_id' => $publisherId]);
        $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enrich data with actual counts and real values for frontend
        foreach ($resources as &$resource) {
            // Use actual image from DB if available, otherwise use default
            $resource['image'] = $resource['image_url'] ?? 'https://via.placeholder.com/400x200?text=Resource+Image'; // Default image

            // Format publisher info
            $resource['creator'] = [
                'name' => $resource['publisher_name'] ?? 'Unknown Publisher',
                'avatar' => $resource['publisher_avatar'] ?: 'https://api.placeholder.com/40/40?text=' . strtoupper(substr($resource['publisher_name'] ?? 'U', 0, 1)),
                'badge' => $resource['publisher_badge'] ?: 'Community Member'
            ];

            // Resources don't typically have participants like actions, but they may have responders
            $resource['participants'] = 0; // Could be expanded to track who has responded to the resource

            // Get actual comment count for this resource
            $commentStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE resource_id = :id");
            $commentStmt->execute([':id' => $resource['id']]);
            $commentCount = $commentStmt->fetch(PDO::FETCH_ASSOC);
            $resource['comment_count'] = $commentCount['count'];

            // Resources don't have start_time or end_time
            $resource['date'] = (new DateTime($resource['created_at']))->format('M d, Y H:i'); // Use created_at as date
            $resource['duration'] = 'N/A'; // Resources don't have duration

            // Set tags to empty array for now (can be extended later)
            $resource['tags'] = [];
        }
        return $resources;
    }

    // Find resource by ID
    public function findById($id) {
        $sql = "SELECT r.*, u.name as publisher_name, u.avatar_url as publisher_avatar, u.badge as publisher_badge
                FROM resources r
                LEFT JOIN users u ON r.publisher_id = u.id
                WHERE r.id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $resource = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resource) {
            // Use actual image from DB if available, otherwise use default
            $resource['image'] = $resource['image_url'] ?? 'https://via.placeholder.com/400x200?text=Resource+Image'; // Default image

            // Format publisher info
            $resource['creator'] = [
                'name' => $resource['publisher_name'] ?? 'Unknown Publisher',
                'avatar' => $resource['publisher_avatar'] ?: 'https://api.placeholder.com/40/40?text=' . strtoupper(substr($resource['publisher_name'] ?? 'U', 0, 1)),
                'badge' => $resource['publisher_badge'] ?: 'Community Member'
            ];

            // Resources don't typically have participants like actions, but they may have responders
            $resource['participants'] = 0; // Could be expanded to track who has responded to the resource

            // Get actual comment count for this resource
            $commentStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE resource_id = :id");
            $commentStmt->execute([':id' => $id]);
            $commentCount = $commentStmt->fetch(PDO::FETCH_ASSOC);
            $resource['comment_count'] = $commentCount['count'];

            // Resources don't have start_time or end_time
            $resource['date'] = (new DateTime($resource['created_at']))->format('M d, Y H:i'); // Use created_at as date
            $resource['duration'] = 'N/A'; // Resources don't have duration

            // Set tags to empty array for now (can be extended later)
            $resource['tags'] = [];
        }

        return $resource;
    }

    // Update a resource
    public function update($id, $data) {
        error_log("Resource::update() called for ID: " . $id . " with data: " . print_r($data, true));

        // Get the current resource to access the old image URL
        $currentResource = $this->findById($id);
        $oldImageUrl = $currentResource ? $currentResource['image_url'] : null;

        $sql = "UPDATE resources SET";
        $params = [];
        $sqlParts = [];

        // Only include fields that are provided in the data
        if (isset($data['resource_name'])) {
            $sqlParts[] = "resource_name = :resource_name";
            $params[':resource_name'] = $data['resource_name'];
        }
        if (isset($data['description'])) {
            $sqlParts[] = "description = :description";
            $params[':description'] = $data['description'];
        }
        if (isset($data['type'])) {
            $sqlParts[] = "type = :type";
            $params[':type'] = $data['type'];
        }
        if (isset($data['category'])) {
            $sqlParts[] = "category = :category";
            $params[':category'] = $data['category'];
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
            error_log("Resource::update() - SQL Error: " . print_r($stmt->errorInfo(), true));
        }

        // Delete old image if a new one was uploaded and the old one exists
        if ($success && isset($data['image_url']) && !empty($oldImageUrl) && $oldImageUrl !== $data['image_url']) {
            require_once '../utils/imageUpload.php';
            ImageUpload::deleteImage($oldImageUrl);
        }

        return $success;
    }

    // Delete a resource
    public function delete($id) {
        // Get the resource to access the image URL before deletion
        $resource = $this->findById($id);
        $imageUrl = $resource ? $resource['image_url'] : null;

        // First delete related comments
        $deleteCommentsStmt = $this->pdo->prepare("DELETE FROM comments WHERE resource_id = :id");
        $deleteCommentsStmt->execute([':id' => $id]);

        // Then delete the resource itself
        $sql = "DELETE FROM resources WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([':id' => $id]);

        // Delete the associated image file if it exists
        if ($success && !empty($imageUrl)) {
            require_once '../utils/imageUpload.php';
            ImageUpload::deleteImage($imageUrl);
        }

        return $success;
    }

    // Approve a resource
    public function approve($id) {
        // First get the resource details to use for notification
        $resource = $this->findById($id);
        if (!$resource) {
            return false;
        }

        $sql = "UPDATE resources SET status = 'approved' WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([':id' => $id]);

        // Create notification if approval was successful
        if ($result) {
            // Include the notification functionality
            require_once '../model/notification.php';
            $notification = new Notification($this->pdo);
            $notification->createResourceApprovedNotification($resource['publisher_id'], $id, $resource['resource_name']);
        }

        return $result;
    }

    // Reject a resource
    public function reject($id) {
        // First get the resource details to use for notification
        $resource = $this->findById($id);
        if (!$resource) {
            return false;
        }

        $sql = "UPDATE resources SET status = 'rejected' WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([':id' => $id]);

        // Create notification if rejection was successful
        if ($result) {
            // Include the notification functionality
            require_once '../model/notification.php';
            $notification = new Notification($this->pdo);
            $notification->createResourceRejectedNotification($resource['publisher_id'], $id, $resource['resource_name']);
        }

        return $result;
    }

    // Get pending resources (for admin approval)
    public function getPending() {
        $sql = "SELECT r.*, u.name as publisher_name, u.avatar_url as publisher_avatar, u.badge as publisher_badge
                FROM resources r
                LEFT JOIN users u ON r.publisher_id = u.id
                WHERE r.status = 'pending'
                ORDER BY r.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enrich data with actual counts and real values for frontend
        foreach ($resources as &$resource) {
            // Use actual image from DB if available, otherwise use default
            $resource['image'] = $resource['image_url'] ?? 'https://via.placeholder.com/400x200?text=Resource+Image'; // Default image

            // Format publisher info
            $resource['creator'] = [
                'name' => $resource['publisher_name'] ?? 'Unknown Publisher',
                'avatar' => $resource['publisher_avatar'] ?: 'https://api.placeholder.com/40/40?text=' . strtoupper(substr($resource['publisher_name'] ?? 'U', 0, 1)),
                'badge' => $resource['publisher_badge'] ?: 'Community Member'
            ];

            // Resources don't typically have participants like actions, but they may have responders
            $resource['participants'] = 0; // Could be expanded to track who has responded to the resource

            // Get actual comment count for this resource
            $commentStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE resource_id = :id");
            $commentStmt->execute([':id' => $resource['id']]);
            $commentCount = $commentStmt->fetch(PDO::FETCH_ASSOC);
            $resource['comment_count'] = $commentCount['count'];

            // Resources don't have start_time or end_time
            $resource['date'] = (new DateTime($resource['created_at']))->format('M d, Y H:i'); // Use created_at as date
            $resource['duration'] = 'N/A'; // Resources don't have duration

            // Set tags to empty array for now (can be extended later)
            $resource['tags'] = [];
        }
        return $resources;
    }

    // Get approved resources (for public display)
    public function getApproved() {
        $sql = "SELECT r.*, u.name as publisher_name, u.avatar_url as publisher_avatar, u.badge as publisher_badge
                FROM resources r
                LEFT JOIN users u ON r.publisher_id = u.id
                WHERE r.status = 'approved'
                ORDER BY r.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enrich data with actual counts and real values for frontend
        foreach ($resources as &$resource) {
            // Use actual image from DB if available, otherwise use default
            $resource['image'] = $resource['image_url'] ?? 'https://via.placeholder.com/400x200?text=Resource+Image'; // Default image

            // Format publisher info
            $resource['creator'] = [
                'name' => $resource['publisher_name'] ?? 'Unknown Publisher',
                'avatar' => $resource['publisher_avatar'] ?: 'https://api.placeholder.com/40/40?text=' . strtoupper(substr($resource['publisher_name'] ?? 'U', 0, 1)),
                'badge' => $resource['publisher_badge'] ?: 'Community Member'
            ];

            // Resources don't typically have participants like actions, but they may have responders
            $resource['participants'] = 0; // Could be expanded to track who has responded to the resource

            // Get actual comment count for this resource
            $commentStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE resource_id = :id");
            $commentStmt->execute([':id' => $resource['id']]);
            $commentCount = $commentStmt->fetch(PDO::FETCH_ASSOC);
            $resource['comment_count'] = $commentCount['count'];

            // Resources don't have start_time or end_time
            $resource['date'] = (new DateTime($resource['created_at']))->format('M d, Y H:i'); // Use created_at as date
            $resource['duration'] = 'N/A'; // Resources don't have duration

            // Set tags to empty array for now (can be extended later)
            $resource['tags'] = [];
        }
        return $resources;
    }

    // Get resources by status
    public function getByStatus($status) {
        $sql = "SELECT r.*, u.name as publisher_name, u.avatar_url as publisher_avatar, u.badge as publisher_badge
                FROM resources r
                LEFT JOIN users u ON r.publisher_id = u.id
                WHERE r.status = :status
                ORDER BY r.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':status' => $status]);
        $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enrich data with actual counts and real values for frontend
        foreach ($resources as &$resource) {
            // Use actual image from DB if available, otherwise use default
            $resource['image'] = $resource['image_url'] ?? 'https://via.placeholder.com/400x200?text=Resource+Image'; // Default image

            // Format publisher info
            $resource['creator'] = [
                'name' => $resource['publisher_name'] ?? 'Unknown Publisher',
                'avatar' => $resource['publisher_avatar'] ?: 'https://api.placeholder.com/40/40?text=' . strtoupper(substr($resource['publisher_name'] ?? 'U', 0, 1)),
                'badge' => $resource['publisher_badge'] ?: 'Community Member'
            ];

            // Resources don't typically have participants like actions, but they may have responders
            $resource['participants'] = 0; // Could be expanded to track who has responded to the resource

            // Get actual comment count for this resource
            $commentStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE resource_id = :id");
            $commentStmt->execute([':id' => $resource['id']]);
            $commentCount = $commentStmt->fetch(PDO::FETCH_ASSOC);
            $resource['comment_count'] = $commentCount['count'];

            // Resources don't have start_time or end_time
            $resource['date'] = (new DateTime($resource['created_at']))->format('M d, Y H:i'); // Use created_at as date
            $resource['duration'] = 'N/A'; // Resources don't have duration

            // Set tags to empty array for now (can be extended later)
            $resource['tags'] = [];
        }
        return $resources;
    }

    // Get resources by country
    public function getByCountry($country) {
        try {
            $sql = "SELECT r.*, u.name as publisher_name, u.avatar_url as publisher_avatar, u.badge as publisher_badge
                    FROM resources r
                    LEFT JOIN users u ON r.publisher_id = u.id
                    WHERE (r.country = :country OR LOWER(r.country) = LOWER(:country))
                    AND r.status = 'approved'
                    ORDER BY r.created_at DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':country', $country, PDO::PARAM_STR);
            $stmt->execute();

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Process each resource to enrich with additional information
            foreach ($results as &$resource) {
                // Use actual image from DB if available, otherwise use default
                $resource['image'] = $resource['image_url'] ?? 'https://via.placeholder.com/400x200?text=Resource+Image';

                // Format publisher info
                $resource['creator'] = [
                    'name' => $resource['publisher_name'] ?? 'Unknown Publisher',
                    'avatar' => $resource['publisher_avatar'] ?: 'https://api.placeholder.com/40/40?text=' . strtoupper(substr($resource['publisher_name'] ?? 'U', 0, 1)),
                    'badge' => $resource['publisher_badge'] ?: 'Community Member'
                ];

                // Resources don't typically have participants like actions, but they may have responders
                $resource['participants'] = 0; // Could be expanded to track who has responded to the resource

                // Get actual comment count for this resource
                $commentStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE resource_id = :id");
                $commentStmt->execute([':id' => $resource['id']]);
                $commentCount = $commentStmt->fetch(PDO::FETCH_ASSOC);
                $resource['comment_count'] = (int)($commentCount['count'] ?? 0);

                // Resources don't have start_time or end_time
                $resource['date'] = (new DateTime($resource['created_at']))->format('M d, Y H:i'); // Use created_at as date
                $resource['duration'] = 'N/A'; // Resources don't have duration

                // Set tags to empty array for now (can be extended later)
                $resource['tags'] = [];
            }

            return $results;
        } catch (PDOException $e) {
            error_log("Error getting resources by country: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    // Create a notification for when a resource is created
    private function createResourceCreatedNotification($userId, $resourceId, $resourceName) {
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
                    ':type' => 'resource_created',
                    ':message' => "New resource '{$resourceName}' has been submitted for approval by user ID {$userId}.",
                    ':related_id' => $resourceId
                ];

                $notificationStmt->execute($notificationParams);
            }
        } catch (Exception $e) {
            error_log("Error creating resource created notification: " . $e->getMessage());
        }
    }

}