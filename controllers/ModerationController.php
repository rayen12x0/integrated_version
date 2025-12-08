<?php
// controllers/ModerationController.php
// Moderation Controller to handle admin moderation tools via API

require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../model/Report.php";
require_once __DIR__ . "/../model/Comment.php";
require_once __DIR__ . "/../model/Story.php";
require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../utils/AuthHelper.php";
require_once __DIR__ . "/../utils/ApiResponse.php";

class ModerationController {
    private $pdo;
    private $report;
    private $comment;
    private $story;
    private $user;

    public function __construct() {
        // Add CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        $this->pdo = Config::getConnexion();
        $this->report = new Report($this->pdo);
        $this->comment = new Comment($this->pdo);
        $this->story = new Story($this->pdo);
        $this->user = new User($this->pdo);
    }

    private function requireAdmin() {
        if (!AuthHelper::isAdmin()) {
            ApiResponse::error("Access denied. Admin privileges required.", 403);
            exit();
        }
    }

    /**
     * Moderation Dashboard Stats
     */
    public function getDashboardStats() {
        $this->requireAdmin();
        header("Content-Type: application/json");

        try {
            // Get report statistics
            $reportStats = $this->report->getStatistics();

            // Get pending reports count
            $pendingReportsCount = $this->report->getPending()->rowCount();

            // Get flagged comments count
            $flaggedCommentsCount = $this->comment->getFlagged()->rowCount();

            // Get most reported stories
            $mostReported = $this->report->getMostReported(5)->fetchAll(PDO::FETCH_ASSOC);

            // Get banned users count
            $bannedUsersCount = $this->report->getBannedUsers()->rowCount();

            // Get story statistics using the existing getStatistics method
            $storyStats = $this->story->getStatistics();

            $data = [
                'report_stats' => $reportStats,
                'pending_reports_count' => $pendingReportsCount,
                'flagged_comments_count' => $flaggedCommentsCount,
                'most_reported_stories' => $mostReported,
                'banned_users_count' => $bannedUsersCount,
                'total_stories' => $storyStats['total_stories'] ?? 0,  // Use actual total stories count
                'pending_stories_count' => $storyStats['pending_stories'] ?? 0  // Use actual pending stories count
            ];

            ApiResponse::success($data, 'Moderation stats retrieved successfully', 200);
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Get Reports
     */
    public function getReports() {
        $this->requireAdmin();
        header("Content-Type: application/json");

        try {
            $filters = [];
            
            if (isset($_GET['status']) && !empty($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }
            
            if (isset($_GET['reason']) && !empty($_GET['reason'])) {
                $filters['reason'] = $_GET['reason'];
            }

            $reports = $this->report->getAll($filters)->fetchAll(PDO::FETCH_ASSOC);

            ApiResponse::success($reports, 'Reports retrieved successfully', 200);
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Get Single Report Details
     */
    public function getReportDetails($id) {
        $this->requireAdmin();
        header("Content-Type: application/json");

        try {
            if (empty($id)) {
                ApiResponse::error("Invalid report ID", 400);
                return;
            }

            // Get the report first to determine its type
            $reportQuery = "SELECT r.*,
                            u.name as reporter_name,
                            u.email as reporter_email
                    FROM reports r
                    INNER JOIN users u ON r.reporter_id = u.id
                    WHERE r.id = :report_id";

            $stmt = $this->pdo->prepare($reportQuery);
            $stmt->bindParam(":report_id", $id);
            $stmt->execute();

            $report = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$report) {
                ApiResponse::error("Report not found", 404);
                return;
            }

            // Get the reported item details based on its type
            $itemDetails = null;
            switch ($report['reported_item_type']) {
                case 'story':
                    $itemQuery = "SELECT s.*, u.name as author_name, u.email as author_email
                                  FROM stories s
                                  LEFT JOIN users u ON s.creator_id = u.id
                                  WHERE s.id = :item_id";
                    break;
                case 'action':
                    $itemQuery = "SELECT a.*, u.name as creator_name, u.email as creator_email
                                  FROM actions a
                                  LEFT JOIN users u ON a.creator_id = u.id
                                  WHERE a.id = :item_id";
                    break;
                case 'resource':
                    $itemQuery = "SELECT r.*, u.name as publisher_name, u.email as publisher_email
                                  FROM resources r
                                  LEFT JOIN users u ON r.publisher_id = u.id
                                  WHERE r.id = :item_id";
                    break;
                case 'comment':
                    $itemQuery = "SELECT c.*, u.name as user_name, u.email as user_email
                                  FROM comments c
                                  LEFT JOIN users u ON c.user_id = u.id
                                  WHERE c.id = :item_id";
                    break;
                case 'user':
                    $itemQuery = "SELECT u.name, u.email, u.role, u.created_at as user_created_at
                                  FROM users u
                                  WHERE u.id = :item_id";
                    break;
                default:
                    $itemQuery = null;
            }

            if ($itemQuery) {
                $itemStmt = $this->pdo->prepare($itemQuery);
                $itemStmt->bindParam(":item_id", $report['reported_item_id']);
                $itemStmt->execute();
                $itemDetails = $itemStmt->fetch(PDO::FETCH_ASSOC);
            }

            // Add item details to the report
            $report['item_details'] = $itemDetails;

            ApiResponse::success($report, 'Report details retrieved successfully', 200);
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Take Action on Report
     */
    public function takeAction() {
        $this->requireAdmin();
        header("Content-Type: application/json");
        $input = json_decode(file_get_contents("php://input"), true);

        try {
            if (!$input || !isset($input['report_id']) || !isset($input['action'])) {
                ApiResponse::error("Invalid input", 400);
                return;
            }

            $report_id = $input['report_id'];
            $action = $input['action'];
            $admin_notes = trim($input['admin_notes'] ?? '');
            $currentUser = AuthHelper::getCurrentUser();
            $adminId = $currentUser['id'];

            $this->report->id = $report_id;
            $message = '';

            switch ($action) {
                case 'dismiss':
                    $this->report->updateStatus($report_id, 'dismissed', $admin_notes);
                    $message = 'Report dismissed.';
                    break;

                case 'delete_story':
                    $story_id = $input['story_id'] ?? null;
                    if ($story_id) {
                        $this->story->id = $story_id;
                        $this->story->delete();
                        $this->report->updateStatus($report_id, 'action_taken', $admin_notes);
                        $message = 'Story deleted and report closed.';
                    } else {
                        ApiResponse::error("Story ID required for this action", 400);
                        return;
                    }
                    break;

                case 'ban_user':
                    $user_id = $input['user_id'] ?? null;
                    $ban_reason = $input['ban_reason'] ?? 'Violated community guidelines';
                    if ($user_id) {
                        $this->report->banUser($user_id, $adminId, $ban_reason);
                        $this->report->updateStatus($report_id, 'action_taken', $admin_notes);
                        $message = 'User banned and report closed.';
                    } else {
                        ApiResponse::error("User ID required for this action", 400);
                        return;
                    }
                    break;

                case 'delete_and_ban':
                    $story_id = $input['story_id'] ?? null;
                    $user_id = $input['user_id'] ?? null;
                    $ban_reason = $input['ban_reason'] ?? 'Severe violation of community guidelines';

                    if ($story_id && $user_id) {
                        $this->story->id = $story_id;
                        $this->story->delete();
                        $this->report->banUser($user_id, $adminId, $ban_reason);
                        $this->report->updateStatus($report_id, 'action_taken', $admin_notes);
                        $message = 'Story deleted, user banned, and report closed.';
                    } else {
                        ApiResponse::error("Story ID and User ID required for this action", 400);
                        return;
                    }
                    break;

                case 'reviewed':
                    $this->report->updateStatus($report_id, 'reviewed', $admin_notes);
                    $message = 'Report marked as reviewed.';
                    break;

                default:
                    ApiResponse::error("Invalid action", 400);
                    return;
            }

            ApiResponse::success(null, $message, 200);

        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Get Banned Users
     */
    public function getBannedUsers() {
        $this->requireAdmin();
        header("Content-Type: application/json");

        try {
            $bannedUsers = $this->report->getBannedUsers()->fetchAll(PDO::FETCH_ASSOC);
            ApiResponse::success($bannedUsers, 'Banned users retrieved successfully', 200);
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Unban User
     */
    public function unbanUser() {
        $this->requireAdmin();
        header("Content-Type: application/json");
        $input = json_decode(file_get_contents("php://input"), true);

        try {
            if (!$input || !isset($input['user_id'])) {
                ApiResponse::error("Invalid input", 400);
                return;
            }

            $user_id = $input['user_id'];

            if ($this->report->unbanUser($user_id)) {
                ApiResponse::success(null, 'User unbanned successfully', 200);
            } else {
                ApiResponse::error("Failed to unban user", 500);
            }
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Get Flagged Comments
     */
    public function getFlaggedComments() {
        $this->requireAdmin();
        header("Content-Type: application/json");

        try {
            $flaggedComments = $this->comment->getFlagged()->fetchAll(PDO::FETCH_ASSOC);
            ApiResponse::success($flaggedComments, 'Flagged comments retrieved successfully', 200);
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Approve Comment
     */
    public function approveComment() {
        $this->requireAdmin();
        header("Content-Type: application/json");
        $input = json_decode(file_get_contents("php://input"), true);

        try {
            if (!$input || !isset($input['comment_id'])) {
                ApiResponse::error("Invalid input", 400);
                return;
            }

            $this->comment->id = $input['comment_id'];

            if ($this->comment->approve()) {
                ApiResponse::success(null, 'Comment approved', 200);
            } else {
                ApiResponse::error("Failed to approve comment", 500);
            }
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Delete Comment
     */
    public function deleteComment() {
        $this->requireAdmin();
        header("Content-Type: application/json");
        $input = json_decode(file_get_contents("php://input"), true);

        try {
            if (!$input || !isset($input['comment_id'])) {
                ApiResponse::error("Invalid input", 400);
                return;
            }

            $this->comment->id = $input['comment_id'];

            if ($this->comment->hardDelete()) {
                ApiResponse::success(null, 'Comment deleted permanently', 200);
            } else {
                ApiResponse::error("Failed to delete comment", 500);
            }
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500);
        }
    }
}
?>
