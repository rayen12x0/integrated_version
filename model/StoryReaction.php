<?php
// model/StoryReaction.php
// Story Reaction model with PDO database operations for story reactions

class StoryReaction
{
    private $pdo;

    // Constructor receives database connection
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Create a new story reaction in the database
    public function create($data) {
        error_log("StoryReaction::create() called with data: " . print_r($data, true));

        try {
            // Check if the story_reactions table exists first
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'story_reactions'");
            if ($tableCheck->rowCount() == 0) {
                error_log("story_reactions table does not exist");
                return false; // Return false if table doesn't exist
            }

            // Check if user has already reacted with this type to prevent duplicates
            $checkSql = "SELECT id FROM story_reactions WHERE story_id = :story_id AND user_id = :user_id AND reaction_type = :reaction_type LIMIT 1";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([
                ':story_id' => $data['story_id'] ?? null,
                ':user_id' => $data['user_id'] ?? null,
                ':reaction_type' => $data['reaction_type'] ?? null
            ]);

            if ($checkStmt->rowCount() > 0) {
                // User already has this reaction, return true but indicate no new entry was created
                return true;
            }

            // SQL query to insert new story reaction
            $sql = "INSERT INTO story_reactions (story_id, user_id, reaction_type)
                    VALUES (:story_id, :user_id, :reaction_type)";

            // Prepare the statement
            $stmt = $this->pdo->prepare($sql);

            // Execute with data provided
            $params = [
                ':story_id' => $data['story_id'] ?? null,
                ':user_id' => $data['user_id'] ?? null,
                ':reaction_type' => $data['reaction_type'] ?? null
            ];

            $success = $stmt->execute($params);
            if (!$success) {
                error_log("StoryReaction::create() - SQL Error: " . print_r($stmt->errorInfo(), true));
            }
            return $success;
        } catch (PDOException $e) {
            error_log("Database error in create: " . $e->getMessage());
            return false; // Return false on error
        } catch (Exception $e) {
            error_log("Error in create: " . $e->getMessage());
            return false; // Return false on error
        }
    }

    // Toggle a story reaction (add if not exists, remove if exists)
    public function toggle($storyId, $userId, $reactionType) {
        error_log("StoryReaction::toggle() called for story_id: $storyId, user_id: $userId, reaction_type: $reactionType");

        try {
            // Check if the story_reactions table exists first
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'story_reactions'");
            if ($tableCheck->rowCount() == 0) {
                error_log("story_reactions table does not exist");
                return ['success' => false, 'message' => 'Reactions table not found', 'action' => 'none'];
            }

            // Check if user has already reacted with this type
            $checkSql = "SELECT id FROM story_reactions WHERE story_id = :story_id AND user_id = :user_id AND reaction_type = :reaction_type LIMIT 1";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([':story_id' => $storyId, ':user_id' => $userId, ':reaction_type' => $reactionType]);

            if ($checkStmt->rowCount() > 0) {
                // User already has this reaction, remove it
                $deleteSql = "DELETE FROM story_reactions WHERE story_id = :story_id AND user_id = :user_id AND reaction_type = :reaction_type";
                $deleteStmt = $this->pdo->prepare($deleteSql);
                $result = $deleteStmt->execute([':story_id' => $storyId, ':user_id' => $userId, ':reaction_type' => $reactionType]);

                if ($result) {
                    return ['success' => true, 'message' => 'Reaction removed', 'action' => 'removed'];
                } else {
                    return ['success' => false, 'message' => 'Failed to remove reaction', 'action' => 'none'];
                }
            } else {
                // Add new reaction
                $insertSql = "INSERT INTO story_reactions (story_id, user_id, reaction_type) VALUES (:story_id, :user_id, :reaction_type)";
                $insertStmt = $this->pdo->prepare($insertSql);
                $result = $insertStmt->execute([':story_id' => $storyId, ':user_id' => $userId, ':reaction_type' => $reactionType]);

                if ($result) {
                    return ['success' => true, 'message' => 'Reaction added', 'action' => 'added'];
                } else {
                    return ['success' => false, 'message' => 'Failed to add reaction', 'action' => 'none'];
                }
            }
        } catch (PDOException $e) {
            error_log("Database error in toggle: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'action' => 'none'];
        } catch (Exception $e) {
            error_log("Error in toggle: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'action' => 'none'];
        }
    }

    // Get reactions for a specific story with counts
    public function getByStory($storyId) {
        try {
            // Check if the story_reactions table exists first
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'story_reactions'");
            if ($tableCheck->rowCount() == 0) {
                error_log("story_reactions table does not exist");
                return []; // Return empty array if table doesn't exist
            }

            $sql = "SELECT 
                        reaction_type,
                        COUNT(*) as count
                    FROM story_reactions 
                    WHERE story_id = :story_id 
                    GROUP BY reaction_type";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':story_id' => $storyId]);
            
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
            error_log("Database error in getByStory: " . $e->getMessage());
            return []; // Return empty array on error
        } catch (Exception $e) {
            error_log("Error in getByStory: " . $e->getMessage());
            return []; // Return empty array on error
        }
    }

    // Get reactions by user for a specific story
    public function getUserReactions($storyId, $userId) {
        try {
            // Check if the story_reactions table exists first
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'story_reactions'");
            if ($tableCheck->rowCount() == 0) {
                error_log("story_reactions table does not exist");
                return []; // Return empty array if table doesn't exist
            }

            $sql = "SELECT reaction_type FROM story_reactions WHERE story_id = :story_id AND user_id = :user_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':story_id' => $storyId, ':user_id' => $userId]);
            
            $reactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $userReactionTypes = [];
            foreach ($reactions as $reaction) {
                $userReactionTypes[] = $reaction['reaction_type'];
            }
            
            return $userReactionTypes;
        } catch (PDOException $e) {
            error_log("Database error in getUserReactions: " . $e->getMessage());
            return []; // Return empty array on error
        } catch (Exception $e) {
            error_log("Error in getUserReactions: " . $e->getMessage());
            return []; // Return empty array on error
        }
    }
}