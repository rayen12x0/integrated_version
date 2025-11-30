<?php
// Determine the base path based on where this file is being included from
$basePath = __DIR__ . '/../';

require_once $basePath . 'config/config.php';
require_once $basePath . 'model/notification.php';

class Report {
    private $conn;
    private $notificationModel;

    public function __construct($pdo = null) {
        $this->conn = $pdo ?: Config::getConnexion();
        $this->notificationModel = new Notification($this->conn);
    }

    public function create($data) {
        try {
            // Check for duplicate report
            if ($this->checkDuplicate($data['reporter_id'], $data['reported_item_id'], $data['reported_item_type'])) {
                return ['success' => false, 'message' => 'You have already reported this item.'];
            }

            $query = "INSERT INTO reports (reporter_id, reported_item_id, reported_item_type, report_category, report_reason, status, admin_notes)
                      VALUES (:reporter_id, :reported_item_id, :reported_item_type, :report_category, :report_reason, :status, :admin_notes)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':reporter_id', $data['reporter_id']);
            $stmt->bindParam(':reported_item_id', $data['reported_item_id']);
            $stmt->bindParam(':reported_item_type', $data['reported_item_type']);
            $stmt->bindParam(':report_category', $data['report_category']);
            $stmt->bindParam(':report_reason', $data['report_reason']);
            $stmt->bindValue(':status', 'pending'); // Default status
            $stmt->bindValue(':admin_notes', null, PDO::PARAM_NULL); // Default to null

            if ($stmt->execute()) {
                // Create notification for admins about the new report
                $this->createAdminNotification($data['reported_item_type'], $data['reported_item_id']);

                return ['success' => true, 'message' => 'Report submitted successfully.'];
            } else {
                return ['success' => false, 'message' => 'Failed to submit report.'];
            }
        } catch (PDOException $e) {
            error_log("Report creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    private function checkDuplicate($reporterId, $reportedItemId, $reportedItemType) {
        try {
            $query = "SELECT id FROM reports WHERE reporter_id = :reporter_id AND reported_item_id = :item_id AND reported_item_type = :item_type LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':reporter_id', $reporterId);
            $stmt->bindParam(':item_id', $reportedItemId);
            $stmt->bindParam(':item_type', $reportedItemType);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result !== false; // If a result is found, there's a duplicate
        } catch (PDOException $e) {
            error_log("Check duplicate error in Report model: " . $e->getMessage());
            return false; // In case of error, assume no duplicate to allow submission
        }
    }

    public function getAll($search = null, $limit = 50) {
        try {
            $query = "SELECT r.*, u.name as reporter_name, u.email as reporter_email
                      FROM reports r
                      JOIN users u ON r.reporter_id = u.id";

            $params = [];
            if ($search) {
                $query .= " WHERE (u.name LIKE :search OR r.report_category LIKE :search OR r.report_reason LIKE :search)";
                $params[':search'] = "%$search%";
            }

            $query .= " ORDER BY r.created_at DESC
                      LIMIT :limit";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get all reports error: " . $e->getMessage());
            return [];
        }
    }

    public function getByStatus($status) {
        try {
            $query = "SELECT r.*, u.name as reporter_name, u.email as reporter_email
                      FROM reports r
                      JOIN users u ON r.reporter_id = u.id
                      WHERE r.status = :status
                      ORDER BY r.created_at DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get reports by status error: " . $e->getMessage());
            return [];
        }
    }

    public function getByCategory($category) {
        try {
            $query = "SELECT r.*, u.name as reporter_name, u.email as reporter_email
                      FROM reports r
                      JOIN users u ON r.reporter_id = u.id
                      WHERE r.report_category = :category
                      ORDER BY r.created_at DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':category', $category);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get reports by category error: " . $e->getMessage());
            return [];
        }
    }

    public function getByStatusAndCategory($status, $category) {
        try {
            $query = "SELECT r.*, u.name as reporter_name, u.email as reporter_email
                      FROM reports r
                      JOIN users u ON r.reporter_id = u.id
                      WHERE r.status = :status AND r.report_category = :category
                      ORDER BY r.created_at DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':category', $category);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get reports by status and category error: " . $e->getMessage());
            return [];
        }
    }

    public function getByReporter($reporterId) {
        try {
            $query = "SELECT r.*, u.name as reporter_name, u.email as reporter_email
                      FROM reports r
                      JOIN users u ON r.reporter_id = u.id
                      WHERE r.reporter_id = :reporter_id
                      ORDER BY r.created_at DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':reporter_id', $reporterId);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get reports by reporter error: " . $e->getMessage());
            return [];
        }
    }

    public function getByReportedItem($itemId, $itemType) {
        try {
            $query = "SELECT r.*, u.name as reporter_name, u.email as reporter_email
                      FROM reports r
                      JOIN users u ON r.reporter_id = u.id
                      WHERE r.reported_item_id = :item_id AND r.reported_item_type = :item_type
                      ORDER BY r.created_at DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':item_id', $itemId);
            $stmt->bindParam(':item_type', $itemType);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get reports by item error: " . $e->getMessage());
            return [];
        }
    }

    public function updateStatus($id, $status, $adminNotes = null) {
        try {
            // First, get the current report details to access the reporter's email and current status
            $getReportQuery = "SELECT r.*, u.name as reporter_name, u.email as reporter_email
                        FROM reports r
                        JOIN users u ON r.reporter_id = u.id
                        WHERE r.id = :id";

            $getStmt = $this->conn->prepare($getReportQuery);
            $getStmt->bindParam(':id', $id);
            $getStmt->execute();
            $report = $getStmt->fetch(PDO::FETCH_ASSOC);

            if (!$report) {
                return ['success' => false, 'message' => 'Report not found.'];
            }

            // Determine the effective status (use current status if null is provided)
            $effectiveStatus = ($status !== null) ? $status : $report['status'];

            // Only include status in the update if it's provided (not null)
            if ($status !== null) {
                $query = "UPDATE reports SET status = :status, admin_notes = :admin_notes, updated_at = CURRENT_TIMESTAMP WHERE id = :id";

                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':admin_notes', $adminNotes);
            } else {
                // Update only admin notes without changing status
                $query = "UPDATE reports SET admin_notes = :admin_notes, updated_at = CURRENT_TIMESTAMP WHERE id = :id";

                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->bindParam(':admin_notes', $adminNotes);
            }

            if ($stmt->execute()) {
                // Send email notification to reporter about status change only if status actually changed
                if ($status !== null && $status !== $report['status']) {
                    $this->sendStatusUpdateEmail($report, $effectiveStatus, $adminNotes);
                    $message = 'Report status and notes updated successfully.';
                } elseif ($status === null && $adminNotes !== null) {
                    // Only notes were updated
                    $message = 'Report admin notes updated successfully.';
                } else {
                    // Both status and notes were updated
                    $this->sendStatusUpdateEmail($report, $effectiveStatus, $adminNotes);
                    $message = 'Report status and notes updated successfully.';
                }

                return ['success' => true, 'message' => $message];
            } else {
                return ['success' => false, 'message' => 'Failed to update report.'];
            }
        } catch (PDOException $e) {
            error_log("Update report status error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function delete($id) {
        try {
            $query = "DELETE FROM reports WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Report deleted successfully.'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete report.'];
            }
        } catch (PDOException $e) {
            error_log("Delete report error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function getFilteredReports($status = null, $category = null, $search = null, $limit = 50) {
        try {
            $query = "SELECT r.*, u.name as reporter_name, u.email as reporter_email
                      FROM reports r
                      JOIN users u ON r.reporter_id = u.id
                      WHERE 1=1"; // Start with a base condition

            $params = [];

            // Add status filter if provided
            if ($status) {
                $query .= " AND r.status = :status";
                $params[':status'] = $status;
            }

            // Add category filter if provided
            if ($category) {
                $query .= " AND r.report_category = :category";
                $params[':category'] = $category;
            }

            // Add search filter if provided
            if ($search) {
                $query .= " AND (u.name LIKE :search OR r.report_category LIKE :search2 OR r.report_reason LIKE :search3)";
                $search_param = "%$search%";
                $params[':search'] = $search_param;
                $params[':search2'] = $search_param;
                $params[':search3'] = $search_param;
            }

            $query .= " ORDER BY r.created_at DESC
                      LIMIT :limit";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get filtered reports error: " . $e->getMessage());
            return [];
        }
    }

    public function getById($id) {
        try {
            $query = "SELECT r.*, u.name as reporter_name, u.email as reporter_email
                      FROM reports r
                      JOIN users u ON r.reporter_id = u.id
                      WHERE r.id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $report = $stmt->fetch(PDO::FETCH_ASSOC);
            return $report ?: null;
        } catch (PDOException $e) {
            error_log("Get report by ID error: " . $e->getMessage());
            return null;
        }
    }

    public function getStatistics() {
        try {
            // Get counts by category
            $categoryQuery = "SELECT report_category, COUNT(*) as count FROM reports GROUP BY report_category";
            $categoryStmt = $this->conn->prepare($categoryQuery);
            $categoryStmt->execute();
            $categoryStats = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

            // Get counts by status
            $statusQuery = "SELECT status, COUNT(*) as count FROM reports GROUP BY status";
            $statusStmt = $this->conn->prepare($statusQuery);
            $statusStmt->execute();
            $statusStats = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total count
            $totalQuery = "SELECT COUNT(*) as total FROM reports";
            $totalStmt = $this->conn->prepare($totalQuery);
            $totalStmt->execute();
            $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];

            return [
                'by_category' => $categoryStats,
                'by_status' => $statusStats,
                'total' => $total
            ];
        } catch (PDOException $e) {
            error_log("Get statistics error: " . $e->getMessage());
            return ['by_category' => [], 'by_status' => [], 'total' => 0];
        }
    }

    private function createAdminNotification($itemType, $itemId) {
        try {
            // Get the admin users with their email addresses
            $adminQuery = "SELECT id, email, name FROM users WHERE role = 'admin'";
            $adminStmt = $this->conn->prepare($adminQuery);
            $adminStmt->execute();
            $adminUsers = $adminStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($adminUsers as $admin) {
                $message = "New user report submitted for " . htmlspecialchars($itemType) . " ID: " . (int)$itemId;
                $notificationData = [
                    'user_id' => $admin['id'],
                    'type' => 'report_created',
                    'message' => $message,
                    'related_id' => $itemId
                ];

                // Create in-app notification
                $this->notificationModel->create($notificationData);

                // Send email notification to admin
                $this->sendReportToAdminEmail($admin['email'], $admin['name'], $itemType, $itemId);
            }
        } catch (PDOException $e) {
            error_log("Create admin notification error: " . $e->getMessage());
        }
    }

    private function sendStatusUpdateEmail($report, $status, $adminNotes = null) {
        require_once __DIR__ . '/../utils/EmailService.php';

        $userName = htmlspecialchars($report['reporter_name']);
        $userEmail = $report['reporter_email'];
        $itemType = htmlspecialchars($report['reported_item_type']);
        $itemId = (int)$report['reported_item_id']; // Ensure it's cast to int for security
        $adminNotes = $adminNotes ? htmlspecialchars($adminNotes) : null;

        // Subject and message based on status
        $subject = "Your Report Status Has Been Updated";
        $message = "Hello {$userName},<br><br>";
        $message .= "Your report for {$itemType} ID: {$itemId} has been updated to: <strong>{$status}</strong>.<br><br>";

        if ($adminNotes) {
            $message .= "<strong>Admin Notes:</strong> {$adminNotes}<br><br>";
        }

        $message .= "Thank you for helping us maintain a positive community.";

        // Send email using EmailService
        EmailService::sendNotificationEmail($userEmail, $report['reporter_name'], $subject, $message);
    }

    private function sendReportToAdminEmail($adminEmail, $adminName, $itemType, $itemId) {
        require_once __DIR__ . '/../utils/EmailService.php';

        $subject = "New Report Submitted - Action Required";
        $adminName = htmlspecialchars($adminName);
        $itemType = htmlspecialchars($itemType);
        $itemId = (int)$itemId; // Ensure it's cast to int for security

        $message = "Hello {$adminName},<br><br>";
        $message .= "A new report has been submitted for {$itemType} ID: {$itemId}.<br><br>";
        $message .= "Please review the report in the admin dashboard and take appropriate action.<br><br>";
        $message .= "Thank you for maintaining our community standards.";

        // Send email using EmailService
        EmailService::sendNotificationEmail($adminEmail, $adminName, $subject, $message);
    }
}
?>