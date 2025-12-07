<?php
// controllers/ReactionController.php
// Reaction Controller to handle business logic for story reactions

require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../utils/AuthHelper.php";
require_once __DIR__ . "/../utils/ApiResponse.php";

class ReactionController
{
    private $pdo;

    // Constructor initializes database connection
    public function __construct() {
        // Add CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        $config = new Config();
        $this->pdo = $config->getConnexion();
    }

    // add() : Add/toggle reaction (POST - JSON response)
    public function add() {
        $this->addStoryReaction();
    }

    // get() : Get reactions for story (GET - JSON)
    public function get() {
        $this->getStoryReactions();
    }

    // getStoriesByReactionType() : Filter stories by reaction
    public function getStoriesByReactionType() {
        header("Content-Type: application/json");
        $type = $_GET['type'] ?? null;
        
        if (!$type) {
            ApiResponse::error("Reaction type required", 400);
            return;
        }

        try {
            $sql = "SELECT s.*, count(sr.id) as reaction_count 
                    FROM stories s 
                    JOIN story_reactions sr ON s.id = sr.story_id 
                    WHERE sr.reaction_type = :type 
                    GROUP BY s.id 
                    ORDER BY reaction_count DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':type', $type);
            $stmt->execute();
            $stories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            ApiResponse::success($stories, "Stories retrieved successfully", 200);
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    // getMostReacted() : Get most reacted stories
    public function getMostReacted() {
        header("Content-Type: application/json");
        
        try {
            $sql = "SELECT s.*, count(sr.id) as total_reactions 
                    FROM stories s 
                    JOIN story_reactions sr ON s.id = sr.story_id 
                    GROUP BY s.id 
                    ORDER BY total_reactions DESC 
                    LIMIT 10";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $stories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            ApiResponse::success($stories, "Most reacted stories retrieved successfully", 200);
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    // Add story reaction method (original)
    public function addStoryReaction() {
        header("Content-Type: application/json");

        try {
            $input = json_decode(file_get_contents("php://input"), true);

            if (!$input || !isset($input['story_id']) || !isset($input['reaction_type'])) {
                ApiResponse::error("Invalid input or missing story_id or reaction_type", 400);
                return;
            }

            $storyId = $input['story_id'];
            $reactionType = $input['reaction_type'];
            $userId = null;

            // Validate reaction type
            $validReactions = ['heart', 'support', 'inspiration', 'solidarity'];
            if (!in_array($reactionType, $validReactions)) {
                ApiResponse::error("Invalid reaction type. Valid types: " . implode(', ', $validReactions), 400);
                return;
            }

            // Check if user is authenticated
            $currentUser = AuthHelper::getCurrentUser();
            if ($currentUser && isset($currentUser['id']) && $currentUser['id']) {
                $userId = $currentUser['id'];
            }

            // Check if user has already reacted with this type
            $checkSql = "SELECT id FROM story_reactions WHERE story_id = :story_id AND user_id = :user_id AND reaction_type = :reaction_type";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->bindParam(':story_id', $storyId);
            $checkStmt->bindParam(':user_id', $userId);
            $checkStmt->bindParam(':reaction_type', $reactionType);
            $checkStmt->execute();

            if ($checkStmt->rowCount() > 0) {
                // If user already has this reaction, remove it (toggle)
                $deleteSql = "DELETE FROM story_reactions WHERE story_id = :story_id AND user_id = :user_id AND reaction_type = :reaction_type";
                $deleteStmt = $this->pdo->prepare($deleteSql);
                $deleteStmt->bindParam(':story_id', $storyId);
                $deleteStmt->bindParam(':user_id', $userId);
                $deleteStmt->bindParam(':reaction_type', $reactionType);
                $result = $deleteStmt->execute();

                if ($result) {
                    ApiResponse::success(['action' => 'removed', 'reaction_type' => $reactionType], 'Reaction removed successfully', 200);
                } else {
                    ApiResponse::error('Failed to remove reaction', 500);
                }
            } else {
                // Add new reaction
                $insertSql = "INSERT INTO story_reactions (story_id, user_id, reaction_type) VALUES (:story_id, :user_id, :reaction_type)";
                $insertStmt = $this->pdo->prepare($insertSql);
                $insertStmt->bindParam(':story_id', $storyId);
                $insertStmt->bindParam(':user_id', $userId);
                $insertStmt->bindParam(':reaction_type', $reactionType);
                $result = $insertStmt->execute();

                if ($result) {
                    ApiResponse::success(['action' => 'added', 'reaction_type' => $reactionType], 'Reaction added successfully', 201);
                } else {
                    ApiResponse::error('Failed to add reaction', 500);
                }
            }
        } catch (Exception $e) {
            error_log("Error in add story reaction: " . $e->getMessage());
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    // Get story reactions method (original)
    public function getStoryReactions() {
        header("Content-Type: application/json");

        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $storyId = null;

            if ($input && isset($input['story_id'])) {
                $storyId = $input['story_id'];
            } elseif (isset($_GET['story_id'])) {
                $storyId = $_GET['story_id'];
            }

            if (!$storyId) {
                ApiResponse::error("Missing story_id", 400);
                return;
            }

            // Get reaction counts
            $sql = "SELECT 
                        reaction_type,
                        COUNT(*) as count
                    FROM story_reactions 
                    WHERE story_id = :story_id 
                    GROUP BY reaction_type";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':story_id', $storyId);
            $stmt->execute();
            
            $reactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Initialize all reaction types
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

            // Check if current user has reacted
            $currentUser = AuthHelper::getCurrentUser();
            $userHasReacted = false;
            $userReactionTypes = [];

            if ($currentUser && isset($currentUser['id']) && $currentUser['id']) {
                $userId = $currentUser['id'];
                $userReactionSql = "SELECT reaction_type FROM story_reactions WHERE story_id = :story_id AND user_id = :user_id";
                $userReactionStmt = $this->pdo->prepare($userReactionSql);
                $userReactionStmt->bindParam(':story_id', $storyId);
                $userReactionStmt->bindParam(':user_id', $userId);
                $userReactionStmt->execute();
                
                $userReactions = $userReactionStmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($userReactions as $reaction) {
                    $userReactionTypes[] = $reaction['reaction_type'];
                }
                
                $userHasReacted = !empty($userReactions);
            }

            ApiResponse::success([
                'reaction_counts' => $counts,
                'user_has_reacted' => $userHasReacted,
                'user_reaction_types' => $userReactionTypes
            ], 'Story reactions retrieved successfully', 200);

        } catch (Exception $e) {
            error_log("Error in get story reactions: " . $e->getMessage());
            ApiResponse::error($e->getMessage(), 500);
        }
    }
}