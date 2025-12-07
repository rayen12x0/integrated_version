<?php
// models/Comment.php
// Enhanced Comment model with PDO database operations and real user/entity integration

class Comment
{
    private $pdo;

    // Constructor receives database connection
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Create a new comment in the database
    public function create($data) {
        error_log("Comment::create() called with data: " . print_r($data, true));

        try {
            // Check if the comments table exists first
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'comments'");
            if ($tableCheck->rowCount() == 0) {
                error_log("Comments table does not exist");
                return ['success' => false, 'message' => 'Comments table does not exist'];
            }

            $content = $data['content'] ?? '';
            $userId = $data['user_id'] ?? null;
            $status = 'active';

            // Check for flagged words
            $flaggedWords = $this->checkForFlaggedWords($content);
            
            if (!empty($flaggedWords)) {
                $criticalFound = false;
                
                foreach ($flaggedWords as $flagged) {
                    // Log the violation
                    $this->logViolation($userId, $flagged);
                    
                    // Check if any critical word requires rejection
                    if ($flagged['auto_action'] === 'reject') {
                        $criticalFound = true;
                    }
                }
                
                // Reject if critical words found
                if ($criticalFound) {
                    return [
                        'success' => false,
                        'message' => 'Your comment contains inappropriate language and cannot be posted.',
                        'flagged' => true,
                        'rejected' => true
                    ];
                }
                
                // Flag but allow if non-critical
                $status = 'flagged';
            }

            // Determine which entity type is being commented on and build appropriate query
            $sql = "INSERT INTO comments (user_id, action_id, resource_id, story_id, content, status)
                    VALUES (:user_id, :action_id, :resource_id, :story_id, :content, :status)";

            // Prepare the statement
            $stmt = $this->pdo->prepare($sql);

            // Execute with data provided
            $params = [
                ':user_id' => $userId,
                ':action_id' => $data['action_id'] ?? null,
                ':resource_id' => $data['resource_id'] ?? null,
                ':story_id' => $data['story_id'] ?? null,
                ':content' => $content,
                ':status' => $status
            ];

            $success = $stmt->execute($params);
            if (!$success) {
                error_log("Comment::create() - SQL Error: " . print_r($stmt->errorInfo(), true));
                return ['success' => false, 'message' => 'Database error'];
            }
            
            $lastId = $this->pdo->lastInsertId();
            
            return [
                'success' => true,
                'id' => $lastId,
                'flagged' => $status === 'flagged',
                'message' => $status === 'flagged' ? 'Your comment has been flagged for review.' : 'Comment posted successfully.'
            ];

        } catch (PDOException $e) {
            error_log("Database error in create: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            error_log("Error in create: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    // Get comments by entity (action, resource, or story) ID
    public function getByEntity($actionId = null, $resourceId = null, $storyId = null) {
        try {
            // Check if the comments table exists first
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'comments'");
            if ($tableCheck->rowCount() == 0) {
                error_log("Comments table does not exist");
                return []; // Return empty array if table doesn't exist
            }

            // Validate that at least one ID is provided
            if (!$actionId && !$resourceId && !$storyId) {
                error_log("No action_id, resource_id, or story_id provided to getByEntity");
                return [];
            }

            $sql = "SELECT c.*, u.name as user_name, u.avatar_url as user_avatar, u.badge as user_badge
                    FROM comments c
                    LEFT JOIN users u ON c.user_id = u.id
                    WHERE ";

            $params = [];

            // Use proper condition based on which ID is provided
            if ($actionId) {
                $sql .= "c.action_id = :entity_id AND c.action_id IS NOT NULL";
                $params[':entity_id'] = $actionId;
            } elseif ($resourceId) {
                $sql .= "c.resource_id = :entity_id AND c.resource_id IS NOT NULL";
                $params[':entity_id'] = $resourceId;
            } elseif ($storyId) {
                $sql .= "c.story_id = :entity_id AND c.story_id IS NOT NULL";
                $params[':entity_id'] = $storyId;
            }

            $sql .= " ORDER BY c.created_at DESC";

            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);

            if (!$result) {
                error_log("SQL execution failed: " . print_r($stmt->errorInfo(), true));
                return [];
            }

            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format comments for frontend
            foreach ($comments as &$comment) {
                // Handle potential NULL values
                $comment['user_name'] = $comment['user_name'] ?? 'Anonymous';
                $comment['user_avatar'] = $comment['user_avatar'] ?: 'https://api.placeholder.com/40/40?text=' . strtoupper(substr($comment['user_name'], 0, 1));
                $comment['user_badge'] = $comment['user_badge'] ?: 'Member';

                $comment['user_info'] = [
                    'name' => $comment['user_name'],
                    'avatar' => $comment['user_avatar'],
                    'badge' => $comment['user_badge']
                ];

                // Safely format the date
                try {
                    $comment['date'] = (new DateTime($comment['created_at']))->format('M d, Y H:i');
                } catch (Exception $dateException) {
                    error_log("Error formatting date: " . $dateException->getMessage());
                    $comment['date'] = 'Unknown date';
                }
            }

            return $comments;
        } catch (PDOException $e) {
            error_log("Database error in getByEntity: " . $e->getMessage());
            return []; // Return empty array on error
        } catch (Exception $e) {
            error_log("Error in getByEntity: " . $e->getMessage());
            return []; // Return empty array on error
        }
    }

    // Get all comments (for admin dashboard)
    public function getAll() {
        try {
            // Check if the comments table exists first
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'comments'");
            if ($tableCheck->rowCount() == 0) {
                error_log("Comments table does not exist");
                return []; // Return empty array if table doesn't exist
            }

            $sql = "SELECT c.*, u.name as user_name, u.avatar_url as user_avatar, u.badge as user_badge,
                           a.title as action_title, r.resource_name as resource_name, s.title as story_title
                    FROM comments c
                    LEFT JOIN users u ON c.user_id = u.id
                    LEFT JOIN actions a ON c.action_id = a.id
                    LEFT JOIN resources r ON c.resource_id = r.id
                    LEFT JOIN stories s ON c.story_id = s.id
                    ORDER BY c.created_at DESC";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute();

            if (!$result) {
                error_log("SQL execution failed in getAll: " . print_r($stmt->errorInfo(), true));
                return [];
            }

            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format comments for frontend
            foreach ($comments as &$comment) {
                // Handle potential NULL values
                $comment['user_name'] = $comment['user_name'] ?? 'Anonymous';
                $comment['user_avatar'] = $comment['user_avatar'] ?: 'https://api.placeholder.com/40/40?text=' . strtoupper(substr($comment['user_name'], 0, 1));
                $comment['user_badge'] = $comment['user_badge'] ?: 'Member';

                $comment['user_info'] = [
                    'name' => $comment['user_name'],
                    'avatar' => $comment['user_avatar'],
                    'badge' => $comment['user_badge']
                ];

                // Safely format the date
                try {
                    $comment['date'] = (new DateTime($comment['created_at']))->format('M d, Y H:i');
                } catch (Exception $dateException) {
                    error_log("Error formatting date in getAll: " . $dateException->getMessage());
                    $comment['date'] = 'Unknown date';
                }
            }

            return $comments;
        } catch (PDOException $e) {
            error_log("Database error in getAll: " . $e->getMessage());
            return []; // Return empty array on error
        } catch (Exception $e) {
            error_log("Error in getAll: " . $e->getMessage());
            return []; // Return empty array on error
        }
    }

    // Find comment by ID
    public function findById($id) {
        try {
            // Check if the comments table exists first
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'comments'");
            if ($tableCheck->rowCount() == 0) {
                error_log("Comments table does not exist");
                return null; // Return null if table doesn't exist
            }

            $sql = "SELECT c.*, u.name as user_name, u.avatar_url as user_avatar, u.badge as user_badge
                    FROM comments c
                    LEFT JOIN users u ON c.user_id = u.id
                    WHERE c.id = :id";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([':id' => $id]);

            if (!$result) {
                error_log("SQL execution failed in findById: " . print_r($stmt->errorInfo(), true));
                return null;
            }

            $comment = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($comment) {
                // Handle potential NULL values
                $comment['user_name'] = $comment['user_name'] ?? 'Anonymous';
                $comment['user_avatar'] = $comment['user_avatar'] ?: 'https://api.placeholder.com/40/40?text=' . strtoupper(substr($comment['user_name'], 0, 1));
                $comment['user_badge'] = $comment['user_badge'] ?: 'Member';

                $comment['user_info'] = [
                    'name' => $comment['user_name'],
                    'avatar' => $comment['user_avatar'],
                    'badge' => $comment['user_badge']
                ];

                // Safely format the date
                try {
                    $comment['date'] = (new DateTime($comment['created_at']))->format('M d, Y H:i');
                } catch (Exception $dateException) {
                    error_log("Error formatting date in findById: " . $dateException->getMessage());
                    $comment['date'] = 'Unknown date';
                }
            }

            return $comment;
        } catch (PDOException $e) {
            error_log("Database error in findById: " . $e->getMessage());
            return null; // Return null on error
        } catch (Exception $e) {
            error_log("Error in findById: " . $e->getMessage());
            return null; // Return null on error
        }
    }

    // Update a comment
    public function update($id, $data) {
        try {
            error_log("Comment::update() called for ID: " . $id . " with data: " . print_r($data, true));

            // Check if the comments table exists first
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'comments'");
            if ($tableCheck->rowCount() == 0) {
                error_log("Comments table does not exist");
                return false; // Return false if table doesn't exist
            }

            $sql = "UPDATE comments SET
                    content = :content
                    WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);

            // Execute with data provided
            $success = $stmt->execute([
                ':content' => $data['content'] ?? '',
                ':id' => $id
            ]);

            if (!$success) {
                error_log("Comment::update() - SQL Error: " . print_r($stmt->errorInfo(), true));
            }
            return $success;
        } catch (PDOException $e) {
            error_log("Database error in update: " . $e->getMessage());
            return false; // Return false on error
        } catch (Exception $e) {
            error_log("Error in update: " . $e->getMessage());
            return false; // Return false on error
        }
    }

    // Delete a comment
    public function delete($id) {
        try {
            // Check if the comments table exists first
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'comments'");
            if ($tableCheck->rowCount() == 0) {
                error_log("Comments table does not exist");
                return false; // Return false if table doesn't exist
            }

            $sql = "DELETE FROM comments WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Database error in delete: " . $e->getMessage());
            return false; // Return false on error
        } catch (Exception $e) {
            error_log("Error in delete: " . $e->getMessage());
            return false; // Return false on error
        }
    }

    // Get comments by user ID
    public function getByUser($userId) {
        try {
            // Check if the comments table exists first
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'comments'");
            if ($tableCheck->rowCount() == 0) {
                error_log("Comments table does not exist");
                return []; // Return empty array if table doesn't exist
            }

            $sql = "SELECT c.*, u.name as user_name, u.avatar_url as user_avatar, u.badge as user_badge,
                           a.title as action_title, r.resource_name as resource_name, s.title as story_title
                    FROM comments c
                    LEFT JOIN users u ON c.user_id = u.id
                    LEFT JOIN actions a ON c.action_id = a.id
                    LEFT JOIN resources r ON c.resource_id = r.id
                    LEFT JOIN stories s ON c.story_id = s.id
                    WHERE c.user_id = :user_id
                    ORDER BY c.created_at DESC";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([':user_id' => $userId]);

            if (!$result) {
                error_log("SQL execution failed in getByUser: " . print_r($stmt->errorInfo(), true));
                return [];
            }

            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format comments for frontend
            foreach ($comments as &$comment) {
                // Handle potential NULL values
                $comment['user_name'] = $comment['user_name'] ?? 'Anonymous';
                $comment['user_avatar'] = $comment['user_avatar'] ?: 'https://api.placeholder.com/40/40?text=' . strtoupper(substr($comment['user_name'], 0, 1));
                $comment['user_badge'] = $comment['user_badge'] ?: 'Community Member';

                $comment['user_info'] = [
                    'name' => $comment['user_name'],
                    'avatar' => $comment['user_avatar'],
                    'badge' => $comment['user_badge']
                ];

                // Format the content
                $content = $comment['content'] ?? '';
                $comment['content_short'] = strlen($content) > 60 ?
                    substr($content, 0, 60) . '...' : $content;

                // Safely format the date
                try {
                    $comment['date'] = (new DateTime($comment['created_at']))->format('M d, Y H:i');
                } catch (Exception $dateException) {
                    error_log("Error formatting date in getByUser: " . $dateException->getMessage());
                    $comment['date'] = 'Unknown date';
                }

                // Identify what the comment is for
                if ($comment['action_id']) {
                    $comment['target_type'] = 'action';
                    $comment['target_title'] = $comment['action_title'] ?: 'Untitled Action';
                } elseif ($comment['resource_id']) {
                    $comment['target_type'] = 'resource';
                    $comment['target_title'] = $comment['resource_name'] ?: 'Untitled Resource';
                } elseif ($comment['story_id']) {
                    $comment['target_type'] = 'story';
                    $comment['target_title'] = $comment['story_title'] ?: 'Untitled Story';
                } else {
                    $comment['target_type'] = 'unknown';
                    $comment['target_title'] = 'Unknown';
                }
            }

            return $comments;
        } catch (PDOException $e) {
            error_log("Database error in getByUser: " . $e->getMessage());
            return []; // Return empty array on error
        } catch (Exception $e) {
            error_log("Error in getByUser: " . $e->getMessage());
            return []; // Return empty array on error
        }
    }

    /**
     * Check for flagged words in content
     */
    private function checkForFlaggedWords($content) {
        try {
            // Check if table exists first
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'flagged_words'");
            if ($tableCheck->rowCount() == 0) {
                return [];
            }

            $query = "SELECT word, category, severity, auto_action 
                    FROM flagged_words";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            
            $flaggedWords = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $found = [];
            
            $contentLower = strtolower($content);
            
            foreach ($flaggedWords as $flagged) {
                if (stripos($contentLower, strtolower($flagged['word'])) !== false) {
                    $found[] = $flagged;
                }
            }
            
            return $found;
        } catch (PDOException $e) {
            error_log("Error checking flagged words: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Log content violation
     */
    private function logViolation($userId, $flaggedWord) {
        try {
            // Check if table exists
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'content_violations'");
            if ($tableCheck->rowCount() == 0) {
                return;
            }

            $query = "INSERT INTO content_violations 
                    SET content_type = 'comment',
                        content_id = 0,
                        user_id = :user_id,
                        flagged_word = :word,
                        word_category = :category,
                        severity = :severity,
                        status = 'pending'";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(":user_id", $userId);
            $stmt->bindParam(":word", $flaggedWord['word']);
            $stmt->bindParam(":category", $flaggedWord['category']);
            $stmt->bindParam(":severity", $flaggedWord['severity']);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error logging violation: " . $e->getMessage());
        }
    }

    /**
     * Get flagged comments (admin)
     */
    public function getFlagged() {
        $query = "SELECT 
                    c.*,
                    u.name as user_name,
                    u.avatar_url as user_avatar,
                    s.title as story_title
                FROM comments c
                INNER JOIN users u ON c.user_id = u.id
                LEFT JOIN stories s ON c.story_id = s.id
                WHERE c.status = 'flagged'
                ORDER BY c.created_at DESC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    /**
     * Approve flagged comment
     */
    public function approve() {
        $query = "UPDATE comments 
                SET status = 'active' 
                WHERE id = :id";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    /**
     * HARD DELETE - Permanently delete comment
     */
    public function hardDelete() {
        $query = "DELETE FROM comments WHERE id = :id";

        $stmt = $this->pdo->prepare($query);
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }
}