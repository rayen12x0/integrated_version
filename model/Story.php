<?php
// model/Story.php
// Enhanced Story model with PDO database operations and real user/creator integration

class Story
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

    // Create a new story in the database
    public function create($data) {
        error_log("Story::create() called with data: " . print_r($data, true));
        // SQL query to insert new story
        $sql = "INSERT INTO stories (creator_id, title, content, excerpt, author_name, author_avatar, theme, language, privacy, status, image_url)
                VALUES (:creator_id, :title, :content, :excerpt, :author_name, :author_avatar, :theme, :language, :privacy, :status, :image_url)";

        // Prepare the statement
        $stmt = $this->pdo->prepare($sql);

        // Map the data to only include fields in the query and set defaults
        $params = [
            ':creator_id' => $data['creator_id'] ?? null,
            ':title' => $data['title'] ?? null,
            ':content' => $data['content'] ?? null,
            ':excerpt' => $data['excerpt'] ?? null,
            ':author_name' => $data['author_name'] ?? null,
            ':author_avatar' => $data['author_avatar'] ?? 'ST',
            ':theme' => $data['theme'] ?? null,
            ':language' => $data['language'] ?? 'en',
            ':privacy' => $data['privacy'] ?? 'public',
            ':status' => $data['status'] ?? 'pending',
            ':image_url' => $data['image_url'] ?? 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIFN0b3J5IEltYWdlPC90ZXh0Pjwvc3ZnPg=='
        ];

        // Execute with proper parameters
        $success = $stmt->execute($params);
        if (!$success) {
            error_log("Story::create() - SQL Error: " . print_r($stmt->errorInfo(), true));
        }

        // The notification will be handled by the controller
        if ($success) {
            $storyId = $this->pdo->lastInsertId();
            // Notification created by controller
        }

        return $success;
    }

    // Get all stories from database and enrich with actual data for frontend
    public function getAll() {
        $sql = "SELECT s.*, u.name as creator_name, u.avatar_url as creator_avatar, u.badge as creator_badge
                FROM stories s
                LEFT JOIN users u ON s.creator_id = u.id
                ORDER BY s.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $stories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enrich data with actual counts and real values for frontend
        foreach ($stories as &$story) {
            // Use actual image from DB if available, otherwise use default
            if (!empty($story['image_url'])) {
                $story['image'] = $story['image_url'];
            } else {
                $story['image'] = $story['image_url'] = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIFN0b3J5IEltYWdlPC90ZXh0Pjwvc3ZnPg==';
            }

            // Format creator info
            $story['creator'] = [
                'name' => $story['creator_name'] ?? $story['author_name'] ?? 'Unknown Creator',
                'avatar' => $story['creator_avatar'] ?: 'https://api.placeholder.com/40/40?text=' . strtoupper(substr($story['creator_name'] ?? $story['author_name'] ?? 'U', 0, 1)),
                'badge' => $story['creator_badge'] ?: 'Community Member'
            ];

            // Get actual comment count
            $commentStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE story_id = :id");
            $commentStmt->execute([':id' => $story['id']]);
            $commentCount = $commentStmt->fetch(PDO::FETCH_ASSOC);
            $story['comment_count'] = $commentCount['count'];

            // Format date for display
            if (isset($story['created_at'])) {
                $story['date'] = (new DateTime($story['created_at']))->format('M d, Y H:i');
            } else {
                $story['date'] = 'N/A';
            }

            // Set tags to empty array for now (can be extended later)
            $story['tags'] = [];
        }
        return $stories;
    }

    // Get stories by creator ID (for user dashboard)
    public function getByCreatorId($creatorId) {
        $sql = "SELECT s.*, u.name as creator_name, u.avatar_url as creator_avatar, u.badge as creator_badge
                FROM stories s
                LEFT JOIN users u ON s.creator_id = u.id
                WHERE s.creator_id = :creator_id
                ORDER BY s.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':creator_id' => $creatorId]);
        $stories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enrich data with actual counts and real values for frontend
        foreach ($stories as &$story) {
            // Use actual image from DB if available, otherwise use default
            if (!empty($story['image_url'])) {
                $story['image'] = $story['image_url'];
            } else {
                $story['image'] = $story['image_url'] = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIFN0b3J5IEltYWdlPC90ZXh0Pjwvc3ZnPg==';
            }

            // Format creator info
            $story['creator'] = [
                'name' => $story['creator_name'] ?? $story['author_name'] ?? 'Unknown Creator',
                'avatar' => $story['creator_avatar'] ?: 'https://api.placeholder.com/40/40?text=' . strtoupper(substr($story['creator_name'] ?? $story['author_name'] ?? 'U', 0, 1)),
                'badge' => $story['creator_badge'] ?: 'Community Member'
            ];

            // Get actual comment count
            $commentStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE story_id = :id");
            $commentStmt->execute([':id' => $story['id']]);
            $commentCount = $commentStmt->fetch(PDO::FETCH_ASSOC);
            $story['comment_count'] = $commentCount['count'];

            // Format date for display
            if (isset($story['created_at'])) {
                $story['date'] = (new DateTime($story['created_at']))->format('M d, Y H:i');
            } else {
                $story['date'] = 'N/A';
            }

            // Set tags to empty array for now (can be extended later)
            $story['tags'] = [];
        }
        return $stories;
    }

    // Find story by ID
    public function findById($id) {
        $sql = "SELECT s.*, u.name as creator_name, u.avatar_url as creator_avatar, u.badge as creator_badge
                FROM stories s
                LEFT JOIN users u ON s.creator_id = u.id
                WHERE s.id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $story = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($story) {
            // Use actual image from DB if available, otherwise use default
            if (!empty($story['image_url'])) {
                $story['image'] = $story['image_url'];
            } else {
                $story['image'] = $story['image_url'] = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIFN0b3J5IEltYWdlPC90ZXh0Pjwvc3ZnPg==';
            }

            // Format creator info
            $story['creator'] = [
                'name' => $story['creator_name'] ?? $story['author_name'] ?? 'Unknown Creator',
                'avatar' => $story['creator_avatar'] ?: 'https://api.placeholder.com/40/40?text=' . strtoupper(substr($story['creator_name'] ?? $story['author_name'] ?? 'U', 0, 1)),
                'badge' => $story['creator_badge'] ?: 'Community Member'
            ];

            // Get actual comment count
            $commentStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE story_id = :id");
            $commentStmt->execute([':id' => $id]);
            $commentCount = $commentStmt->fetch(PDO::FETCH_ASSOC);
            $story['comment_count'] = $commentCount['count'];

            // Format date for display
            if (isset($story['created_at'])) {
                $story['date'] = (new DateTime($story['created_at']))->format('M d, Y H:i');
            } else {
                $story['date'] = 'N/A';
            }

            // Set tags to empty array for now (can be extended later)
            $story['tags'] = [];
        }

        return $story;
    }

    // Update a story
    public function update($id, $data) {
        error_log("Story::update() called for ID: " . $id . " with data: " . print_r($data, true));

        // Get the current story to access the old image URL
        $currentStory = $this->findById($id);
        $oldImageUrl = $currentStory ? $currentStory['image_url'] : null;

        $sql = "UPDATE stories SET";
        $params = [];
        $sqlParts = [];

        // Only include fields that are provided in the data
        if (isset($data['title'])) {
            $sqlParts[] = "title = :title";
            $params[':title'] = $data['title'];
        }
        if (isset($data['content'])) {
            $sqlParts[] = "content = :content";
            $params[':content'] = $data['content'];
        }
        if (isset($data['excerpt'])) {
            $sqlParts[] = "excerpt = :excerpt";
            $params[':excerpt'] = $data['excerpt'];
        }
        if (isset($data['author_name'])) {
            $sqlParts[] = "author_name = :author_name";
            $params[':author_name'] = $data['author_name'];
        }
        if (isset($data['author_avatar'])) {
            $sqlParts[] = "author_avatar = :author_avatar";
            $params[':author_avatar'] = $data['author_avatar'];
        }
        if (isset($data['theme'])) {
            $sqlParts[] = "theme = :theme";
            $params[':theme'] = $data['theme'];
        }
        if (isset($data['language'])) {
            $sqlParts[] = "language = :language";
            $params[':language'] = $data['language'];
        }
        if (isset($data['privacy'])) {
            $sqlParts[] = "privacy = :privacy";
            $params[':privacy'] = $data['privacy'];
        }
        if (isset($data['status'])) {
            $sqlParts[] = "status = :status";
            $params[':status'] = $data['status'];
        }
        if (isset($data['image_url'])) {
            $sqlParts[] = "image_url = :image_url";
            $params[':image_url'] = $data['image_url'];
        }
        if (isset($data['admin_notes'])) {
            $sqlParts[] = "admin_notes = :admin_notes";
            $params[':admin_notes'] = $data['admin_notes'];
        }

        $sql .= " " . implode(", ", $sqlParts) . " WHERE id = :id";
        $params[':id'] = $id;

        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute($params);
        if (!$success) {
            error_log("Story::update() - SQL Error: " . print_r($stmt->errorInfo(), true));
        }

        // Delete old image if a new one was uploaded and the old one exists
        if ($success && isset($data['image_url']) && !empty($oldImageUrl) && $oldImageUrl !== $data['image_url']) {
            require_once __DIR__ . '/../utils/imageUpload.php';
            ImageUpload::deleteImage($oldImageUrl);
        }

        return $success;
    }

    // Delete a story
    public function delete($id) {
        // Get the story to access the image URL before deletion
        $story = $this->findById($id);
        $imageUrl = $story ? $story['image_url'] : null;

        // First delete related comments and story reactions
        $deleteCommentsStmt = $this->pdo->prepare("DELETE FROM comments WHERE story_id = :id");
        $deleteCommentsStmt->execute([':id' => $id]);

        $deleteReactionsStmt = $this->pdo->prepare("DELETE FROM story_reactions WHERE story_id = :id");
        $deleteReactionsStmt->execute([':id' => $id]);

        // Then delete the story itself
        $sql = "DELETE FROM stories WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([':id' => $id]);

        // Delete the associated image file if it exists
        if ($success && !empty($imageUrl)) {
            require_once __DIR__ . '/../utils/imageUpload.php';
            ImageUpload::deleteImage($imageUrl);
        }

        return $success;
    }

    // Approve a story
    public function approve($id) {
        // First get the story details to use for notification
        $story = $this->findById($id);
        if (!$story) {
            return false;
        }

        $sql = "UPDATE stories SET status = 'approved' WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([':id' => $id]);

        // The notification will be handled by the controller
        // No need to create notification here to avoid duplicates

        return $result;
    }

    // Reject a story
    public function reject($id) {
        // First get the story details to use for notification
        $story = $this->findById($id);
        if (!$story) {
            return false;
        }

        $sql = "UPDATE stories SET status = 'rejected' WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([':id' => $id]);

        // The notification will be handled by the controller
        // No need to create notification here to avoid duplicates

        return $result;
    }

    // Get pending stories (for admin approval)
    public function getPending() {
        $sql = "SELECT s.*, u.name as creator_name, u.avatar_url as creator_avatar, u.badge as creator_badge
                FROM stories s
                LEFT JOIN users u ON s.creator_id = u.id
                WHERE s.status = 'pending'
                ORDER BY s.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $stories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enrich data with actual counts and real values for frontend
        foreach ($stories as &$story) {
            // Use actual image from DB if available, otherwise use default
            if (!empty($story['image_url'])) {
                $story['image'] = $story['image_url'];
            } else {
                $story['image'] = $story['image_url'] = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIFN0b3J5IEltYWdlPC90ZXh0Pjwvc3ZnPg==';
            }

            // Format creator info
            $story['creator'] = [
                'name' => $story['creator_name'] ?? $story['author_name'] ?? 'Unknown Creator',
                'avatar' => $story['creator_avatar'] ?: 'https://api.placeholder.com/40/40?text=' . strtoupper(substr($story['creator_name'] ?? $story['author_name'] ?? 'U', 0, 1)),
                'badge' => $story['creator_badge'] ?: 'Community Member'
            ];

            // Get actual comment count
            $commentStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE story_id = :id");
            $commentStmt->execute([':id' => $story['id']]);
            $commentCount = $commentStmt->fetch(PDO::FETCH_ASSOC);
            $story['comment_count'] = $commentCount['count'];

            // Format date for display
            if (isset($story['created_at'])) {
                $story['date'] = (new DateTime($story['created_at']))->format('M d, Y H:i');
            } else {
                $story['date'] = 'N/A';
            }

            // Set tags to empty array for now (can be extended later)
            $story['tags'] = [];
        }
        return $stories;
    }

    // Get approved stories (for public display)
    public function getApproved() {
        $sql = "SELECT s.*, u.name as creator_name, u.avatar_url as creator_avatar, u.badge as creator_badge
                FROM stories s
                LEFT JOIN users u ON s.creator_id = u.id
                WHERE s.status = 'approved'
                ORDER BY s.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $stories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enrich data with actual counts and real values for frontend
        foreach ($stories as &$story) {
            // Use actual image from DB if available, otherwise use default
            if (!empty($story['image_url'])) {
                $story['image'] = $story['image_url'];
            } else {
                $story['image'] = $story['image_url'] = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIFN0b3J5IEltYWdlPC90ZXh0Pjwvc3ZnPg==';
            }

            // Format creator info
            $story['creator'] = [
                'name' => $story['creator_name'] ?? $story['author_name'] ?? 'Unknown Creator',
                'avatar' => $story['creator_avatar'] ?: 'https://api.placeholder.com/40/40?text=' . strtoupper(substr($story['creator_name'] ?? $story['author_name'] ?? 'U', 0, 1)),
                'badge' => $story['creator_badge'] ?: 'Community Member'
            ];

            // Get actual comment count
            $commentStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE story_id = :id");
            $commentStmt->execute([':id' => $story['id']]);
            $commentCount = $commentStmt->fetch(PDO::FETCH_ASSOC);
            $story['comment_count'] = $commentCount['count'];

            // Format date for display
            if (isset($story['created_at'])) {
                $story['date'] = (new DateTime($story['created_at']))->format('M d, Y H:i');
            } else {
                $story['date'] = 'N/A';
            }

            // Set tags to empty array for now (can be extended later)
            $story['tags'] = [];
        }
        return $stories;
    }

    // Increment view count for a story
    public function incrementViews($id) {
        $sql = "UPDATE stories SET views = views + 1 WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }


    // Create notification for story creation
    private function createStoryCreatedNotification($creatorId, $storyId, $title) {
        // Include the notification functionality
        require_once __DIR__ . '/notification.php';
        $notification = new Notification($this->pdo);

        // Find admin user for notification
        $adminStmt = $this->pdo->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        $adminStmt->execute();
        $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            $notification->createStoryCreatedNotification($admin['id'], $storyId, $title);
        }
    }

    // Get reaction counts for a story
    public function getReactionCounts($storyId) {
        try {
            $sql = "SELECT
                        reaction_type,
                        COUNT(*) as count
                    FROM story_reactions
                    WHERE story_id = :story_id
                    GROUP BY reaction_type";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':story_id', $storyId, PDO::PARAM_INT);
            $stmt->execute();

            $reactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Initialize all reaction types with zero counts
            $counts = [
                'heart' => 0,
                'support' => 0,
                'inspiration' => 0,
                'solidarity' => 0,
                'total' => 0
            ];

            foreach ($reactions as $reaction) {
                $type = $reaction['reaction_type'];
                $counts[$type] = $reaction['count'];
                $counts['total'] += $reaction['count'];
            }

            return $counts;
        } catch (PDOException $e) {
            error_log("Database error in getReactionCounts: " . $e->getMessage());
            return []; // Return empty array on error
        } catch (Exception $e) {
            error_log("Error in getReactionCounts: " . $e->getMessage());
            return []; // Return empty array on error
        }
    }

    // Get story statistics
    public function getStatistics() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_stories,
                        SUM(views) as total_views,
                        (SELECT COUNT(*) FROM story_reactions) as total_reactions,
                        (SELECT COUNT(*) FROM comments WHERE story_id IS NOT NULL) as total_comments,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_stories,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_stories,
                        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_stories
                    FROM stories";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getStatistics: " . $e->getMessage());
            return [];
        }
    }
}