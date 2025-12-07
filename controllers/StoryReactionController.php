<?php
// controllers/StoryReactionController.php
// Story Reaction Controller to handle business logic for story reactions

require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../model/StoryReaction.php";
require_once __DIR__ . "/../utils/AuthHelper.php";
require_once __DIR__ . "/../utils/ApiResponse.php";

class StoryReactionController
{
    private $pdo;
    private $storyReaction;

    // Constructor initializes database connection and story reaction model
    public function __construct() {
        // Add CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        $config = new Config();
        $this->pdo = $config->getConnexion();
        $this->storyReaction = new StoryReaction($this->pdo);
    }

    // Create or toggle story reaction method
    public function add() {
        error_log("StoryReactionController::add() called.");

        header("Content-Type: application/json");

        try {
            // Get input data from JSON request
            $input = json_decode(file_get_contents("php://input"), true);
            error_log("Received input: " . print_r($input, true));

            // Validate input
            if (!$input || !isset($input['story_id']) || !isset($input['reaction_type'])) {
                error_log("Missing required fields: story_id or reaction_type");
                ApiResponse::error("Missing required fields: story_id and reaction_type", 400);
                return;
            }

            // Validate reaction type
            $validReactions = ['heart', 'support', 'inspiration', 'solidarity'];
            if (!in_array($input['reaction_type'], $validReactions)) {
                error_log("Invalid reaction type: " . $input['reaction_type']);
                ApiResponse::error("Invalid reaction type. Valid types: " . implode(', ', $validReactions), 400);
                return;
            }

            // Get current user
            $currentUser = AuthHelper::getCurrentUser();
            if (!$currentUser || !isset($currentUser['id']) || !$currentUser['id']) {
                error_log("User not authenticated");
                ApiResponse::error("Authentication required", 401);
                return;
            }

            // Perform the toggle action
            $result = $this->storyReaction->toggle($input['story_id'], $currentUser['id'], $input['reaction_type']);

            if ($result['success']) {
                // Get updated reaction counts for all types
                $reactionCounts = $this->storyReaction->getByStory($input['story_id']);
                
                // Get user's current reactions
                $userReactionTypes = $this->storyReaction->getUserReactions($input['story_id'], $currentUser['id']);
                
                ApiResponse::success([
                    'action' => $result['action'],
                    'reaction_type' => $input['reaction_type'],
                    'reaction_counts' => $reactionCounts,
                    'user_reaction_types' => $userReactionTypes
                ], $result['message'], $result['action'] === 'added' ? 201 : 200);
            } else {
                error_log("Failed to toggle reaction: " . $result['message']);
                ApiResponse::error($result['message'], 500);
            }
        } catch (Exception $e) {
            error_log("Error in add story reaction: " . $e->getMessage());
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    // Get reactions for a story method
    public function get() {
        error_log("StoryReactionController::get() called.");

        header("Content-Type: application/json");

        try {
            // Get story ID from GET or POST
            $storyId = null;
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $storyId = $_GET['story_id'] ?? null;
            } else {
                $input = json_decode(file_get_contents("php://input"), true);
                $storyId = $input['story_id'] ?? null;
            }

            if (!$storyId) {
                error_log("Missing story_id parameter");
                ApiResponse::error("Missing story_id parameter", 400);
                return;
            }

            // Get reaction counts
            $reactionCounts = $this->storyReaction->getByStory($storyId);

            // Check if current user has reacted
            $currentUser = AuthHelper::getCurrentUser();
            $userHasReacted = false;
            $userReactionTypes = [];

            if ($currentUser && isset($currentUser['id']) && $currentUser['id']) {
                $userReactionTypes = $this->storyReaction->getUserReactions($storyId, $currentUser['id']);
                $userHasReacted = !empty($userReactionTypes);
            }

            ApiResponse::success([
                'reaction_counts' => $reactionCounts,
                'user_has_reacted' => $userHasReacted,
                'user_reaction_types' => $userReactionTypes
            ], 'Story reactions retrieved successfully', 200);

        } catch (Exception $e) {
            error_log("Error in get story reactions: " . $e->getMessage());
            ApiResponse::error($e->getMessage(), 500);
        }
    }
}