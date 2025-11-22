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
                return false; // Return false if table doesn't exist
            }

            // SQL query to insert new comment
            $sql = "INSERT INTO comments (user_id, action_id, resource_id, content)
                    VALUES (:user_id, :action_id, :resource_id, :content)";

            // Prepare the statement
            $stmt = $this->pdo->prepare($sql);

            // Execute with data provided
            $params = [
                ':user_id' => $data['user_id'] ?? null,
                ':action_id' => $data['action_id'] ?? null,
                ':resource_id' => $data['resource_id'] ?? null,
                ':content' => $data['content'] ?? ''
            ];

            $success = $stmt->execute($params);
            if (!$success) {
                error_log("Comment::create() - SQL Error: " . print_r($stmt->errorInfo(), true));
            }
            return $success;
        } catch (PDOException $e) {
            error_log("Database error in create: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error in create: " . $e->getMessage());
            return false;
        }
    }

    // Get comments by entity (action or resource) ID
    public function getByEntity($actionId = null, $resourceId = null) {
        try {
            // Check if the comments table exists first
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'comments'");
            if ($tableCheck->rowCount() == 0) {
                error_log("Comments table does not exist");
                return []; // Return empty array if table doesn't exist
            }

            // Validate that at least one ID is provided
            if (!$actionId && !$resourceId) {
                error_log("No action_id or resource_id provided to getByEntity");
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
                           a.title as action_title, r.resource_name as resource_name
                    FROM comments c
                    LEFT JOIN users u ON c.user_id = u.id
                    LEFT JOIN actions a ON c.action_id = a.id
                    LEFT JOIN resources r ON c.resource_id = r.id
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
                           a.title as action_title, r.resource_name as resource_name
                    FROM comments c
                    LEFT JOIN users u ON c.user_id = u.id
                    LEFT JOIN actions a ON c.action_id = a.id
                    LEFT JOIN resources r ON c.resource_id = r.id
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
}