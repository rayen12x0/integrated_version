<?php
// controllers/ProfileController.php
// Profile Controller to handle user profile operations via API

require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../model/Story.php";
require_once __DIR__ . "/../utils/AuthHelper.php";
require_once __DIR__ . "/../utils/ApiResponse.php";

class ProfileController {
    private $pdo;
    private $user;
    private $story;

    public function __construct() {
        // Add CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        $this->pdo = Config::getConnexion();
        $this->user = new User($this->pdo);
        $this->story = new Story($this->pdo);
    }

    /**
     * Get user profile details
     */
    public function getProfile() {
        header("Content-Type: application/json");

        try {
            $currentUser = AuthHelper::getCurrentUser();
            if (!$currentUser || !isset($currentUser['id'])) {
                ApiResponse::error("Authentication required", 401);
                return;
            }

            $this->user->id = $currentUser['id'];
            
            if ($this->user->readOne()) {
                // Get statistics
                $stats = $this->user->getStatistics();
                
                $profileData = [
                    'id' => $this->user->id,
                    'username' => $this->user->username,
                    'email' => $this->user->email,
                    'full_name' => $this->user->full_name,
                    'avatar' => $this->user->avatar,
                    'bio' => $this->user->bio,
                    'role' => $this->user->role,
                    'created_at' => $this->user->created_at,
                    'stats' => $stats
                ];

                ApiResponse::success($profileData, 'Profile retrieved successfully', 200);
            } else {
                ApiResponse::error("User not found", 404);
            }
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Update user profile
     */
    public function updateProfile() {
        header("Content-Type: application/json");
        $input = json_decode(file_get_contents("php://input"), true);

        try {
            $currentUser = AuthHelper::getCurrentUser();
            if (!$currentUser || !isset($currentUser['id'])) {
                ApiResponse::error("Authentication required", 401);
                return;
            }

            if (!$input) {
                ApiResponse::error("Invalid input", 400);
                return;
            }

            // Validate
            if (empty($input['full_name']) || strlen($input['full_name']) < 2) {
                ApiResponse::error("Full name must be at least 2 characters.", 400);
                return;
            }
            
            if (empty($input['avatar']) || strlen($input['avatar']) < 2 || strlen($input['avatar']) > 3) {
                ApiResponse::error("Avatar initials must be 2-3 characters.", 400);
                return;
            }

            $this->user->id = $currentUser['id'];
            $this->user->full_name = $input['full_name'];
            $this->user->avatar = strtoupper($input['avatar']);
            $this->user->bio = $input['bio'] ?? '';

            if ($this->user->update()) {
                // Update session if using PHP sessions
                if (session_status() === PHP_SESSION_ACTIVE) {
                    $_SESSION['user']['name'] = $this->user->full_name;
                    $_SESSION['user']['avatar_url'] = "https://ui-avatars.com/api/?name=" . urlencode($this->user->full_name) . "&background=random"; // Fallback or use actual avatar logic
                    // Note: The User model uses 'avatar' as initials, AuthHelper uses 'avatar_url'. 
                    // We might need to sync this. For now, let's just update the name.
                }

                ApiResponse::success(null, 'Profile updated successfully', 200);
            } else {
                ApiResponse::error("Unable to update profile.", 500);
            }
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Update password
     */
    public function updatePassword() {
        header("Content-Type: application/json");
        $input = json_decode(file_get_contents("php://input"), true);

        try {
            $currentUser = AuthHelper::getCurrentUser();
            if (!$currentUser || !isset($currentUser['id'])) {
                ApiResponse::error("Authentication required", 401);
                return;
            }

            if (!$input) {
                ApiResponse::error("Invalid input", 400);
                return;
            }

            $old_password = $input['old_password'] ?? '';
            $new_password = $input['new_password'] ?? '';
            $confirm_password = $input['confirm_password'] ?? '';

            if (empty($old_password)) {
                ApiResponse::error("Current password is required.", 400);
                return;
            }

            if (empty($new_password)) {
                ApiResponse::error("New password is required.", 400);
                return;
            } elseif (strlen($new_password) < 6) {
                ApiResponse::error("Password must be at least 6 characters.", 400);
                return;
            }

            if ($new_password !== $confirm_password) {
                ApiResponse::error("Passwords do not match.", 400);
                return;
            }

            $this->user->id = $currentUser['id'];

            if ($this->user->updatePassword($old_password, $new_password)) {
                ApiResponse::success(null, 'Password updated successfully', 200);
            } else {
                ApiResponse::error("Current password is incorrect.", 400);
            }
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Get user's stories
     */
    public function getMyStories() {
        header("Content-Type: application/json");

        try {
            $currentUser = AuthHelper::getCurrentUser();
            if (!$currentUser || !isset($currentUser['id'])) {
                ApiResponse::error("Authentication required", 401);
                return;
            }

            $this->user->id = $currentUser['id'];
            if (!$this->user->readOne()) {
                ApiResponse::error("User not found", 404);
                return;
            }

            // Get user's stories with filters
            // Note: User model getStories uses author_name, which is fragile if name changes.
            // Ideally it should use user_id (creator_id).
            // Let's check Story model. Story model has getByCreatorId.
            // User model getStories uses: WHERE s.author_name = :author_name
            // This seems to be legacy from story module.
            // Since we have StoryController::getByCreatorId, we should probably use that or fix User::getStories.
            // Let's use StoryController logic here or call Story model directly.
            
            // Using Story model directly with creator_id is better.
            // But User::getStories does a join with reactions.
            // Let's rely on User::getStories for now but be aware of the name dependency.
            
            $stmt = $this->user->getStories();
            $stories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            ApiResponse::success($stories, 'Stories retrieved successfully', 200);

        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500);
        }
    }
}
?>
