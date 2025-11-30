<?php
// For the ReportController, when included from API files,
// we need to account for the file path structure
// API files are in api/reports/
// Controllers are in controllers/
// So from api/reports/ to config/ is ../../config/

// Direct require paths from API context
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../model/report.php';
require_once __DIR__ . '/../utils/AuthHelper.php';

class ReportController {
    private $conn;
    private $reportModel;

    public function __construct() {
        $this->conn = Config::getConnexion();
        $this->reportModel = new Report($this->conn);
    }

    public function create() {
        try {
            // Check if user is authenticated
            if (!AuthHelper::isLoggedIn()) {
                echo json_encode(['success' => false, 'message' => 'User not authenticated. Please log in to submit reports.']);
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                error_log("Invalid JSON input received in ReportController::create(): " . json_last_error_msg());
                echo json_encode(['success' => false, 'message' => 'Invalid JSON input: ' . json_last_error_msg()]);
                return;
            }

            if ($data === null) {
                error_log("No input data received in ReportController::create()");
                echo json_encode(['success' => false, 'message' => 'No data received. Please ensure your request contains valid report data.']);
                return;
            }

            $userId = $_SESSION['user_id'];

            // Validate required fields
            if (!isset($data['reported_item_id']) || !isset($data['reported_item_type']) ||
                !isset($data['report_category']) || !isset($data['report_reason'])) {

                $missingFields = [];
                if (!isset($data['reported_item_id'])) $missingFields[] = 'reported_item_id';
                if (!isset($data['reported_item_type'])) $missingFields[] = 'reported_item_type';
                if (!isset($data['report_category'])) $missingFields[] = 'report_category';
                if (!isset($data['report_reason'])) $missingFields[] = 'report_reason';

                error_log("Missing required fields in report data: " . implode(', ', $missingFields));
                echo json_encode(['success' => false, 'message' => 'Missing required fields: ' . implode(', ', $missingFields)]);
                return;
            }

            // Validate field values are not empty
            if (empty($data['reported_item_id']) || empty($data['reported_item_type']) ||
                empty($data['report_category']) || empty($data['report_reason'])) {

                $emptyFields = [];
                if (empty($data['reported_item_id'])) $emptyFields[] = 'reported_item_id';
                if (empty($data['reported_item_type'])) $emptyFields[] = 'reported_item_type';
                if (empty($data['report_category'])) $emptyFields[] = 'report_category';
                if (empty($data['report_reason'])) $emptyFields[] = 'report_reason';

                error_log("Empty required fields in report data: " . implode(', ', $emptyFields));
                echo json_encode(['success' => false, 'message' => 'Required fields cannot be empty: ' . implode(', ', $emptyFields)]);
                return;
            }

            // Validate report category is one of the allowed options
            $allowedCategories = ['scam', 'spam', 'inappropriate', 'fake', 'other'];
            if (!in_array($data['report_category'], $allowedCategories)) {
                error_log("Invalid report category: " . $data['report_category']);
                echo json_encode(['success' => false, 'message' => 'Invalid report category. Allowed values are: ' . implode(', ', $allowedCategories)]);
                return;
            }

            // Validate reported item type is one of the allowed options
            $allowedTypes = ['action', 'resource'];
            if (!in_array($data['reported_item_type'], $allowedTypes)) {
                error_log("Invalid reported item type: " . $data['reported_item_type']);
                echo json_encode(['success' => false, 'message' => 'Invalid item type. Allowed values are: ' . implode(', ', $allowedTypes)]);
                return;
            }

            // Validate that the reported item actually exists
            $itemCheckStmt = null;
            if ($data['reported_item_type'] === 'action') {
                $itemCheckStmt = $this->conn->prepare("SELECT id FROM actions WHERE id = :item_id");
            } else {
                $itemCheckStmt = $this->conn->prepare("SELECT id FROM resources WHERE id = :item_id");
            }

            $itemCheckStmt->execute([':item_id' => $data['reported_item_id']]);
            $itemExists = $itemCheckStmt->fetch();

            if (!$itemExists) {
                error_log("Attempted to report non-existent {$data['reported_item_type']} with ID: " . $data['reported_item_id']);
                echo json_encode(['success' => false, 'message' => 'The item you are trying to report does not exist.']);
                return;
            }

            // Prepare data for model
            $reportData = [
                'reporter_id' => $userId,
                'reported_item_id' => $data['reported_item_id'],
                'reported_item_type' => $data['reported_item_type'],
                'report_category' => $data['report_category'],
                'report_reason' => $data['report_reason']
            ];

            $result = $this->reportModel->create($reportData);
            echo json_encode($result);
        } catch (Exception $e) {
            error_log("Error in ReportController::create(): " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An error occurred while processing your report: ' . $e->getMessage()]);
        }
    }

    public function getAll() {
        // Only allow admin access
        if (!AuthHelper::isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $category = isset($_GET['category']) ? $_GET['category'] : null;
        $search = isset($_GET['search']) ? $_GET['search'] : null;

        $reports = $this->reportModel->getFilteredReports($status, $category, $search);

        echo json_encode(['success' => true, 'data' => $reports]);
    }

    public function getByUser() {
        // Check if user is authenticated
        if (!AuthHelper::isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'User not authenticated']);
            return;
        }

        $userId = $_SESSION['user_id'];
        $reports = $this->reportModel->getByReporter($userId);

        echo json_encode(['success' => true, 'data' => $reports]);
    }

    public function getByItem() {
        // Only allow admin access
        if (!AuthHelper::isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        $itemId = isset($_GET['item_id']) ? $_GET['item_id'] : null;
        $itemType = isset($_GET['item_type']) ? $_GET['item_type'] : null;

        if (!$itemId || !$itemType) {
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            return;
        }

        $reports = $this->reportModel->getByReportedItem($itemId, $itemType);

        echo json_encode(['success' => true, 'data' => $reports]);
    }

    public function updateStatus() {
        // Only allow admin access
        if (!AuthHelper::isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }

        $id = $data['id'];
        $status = isset($data['status']) ? $data['status'] : null;
        $adminNotes = isset($data['admin_notes']) ? $data['admin_notes'] : null;

        $result = $this->reportModel->updateStatus($id, $status, $adminNotes);
        echo json_encode($result);
    }

    public function delete() {
        // Only allow admin access
        if (!AuthHelper::isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        $id = isset($_GET['id']) ? $_GET['id'] : null;

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Missing report ID']);
            return;
        }

        $result = $this->reportModel->delete($id);
        echo json_encode($result);
    }

    public function getById() {
        // Only allow admin access
        if (!AuthHelper::isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        $id = isset($_GET['id']) ? $_GET['id'] : null;

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Missing report ID']);
            return;
        }

        $report = $this->reportModel->getById($id);

        if ($report) {
            echo json_encode(['success' => true, 'data' => $report]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Report not found']);
        }
    }

    public function getStatistics() {
        // Only allow admin access
        if (!AuthHelper::isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        $statistics = $this->reportModel->getStatistics();
        echo json_encode(['success' => true, 'data' => $statistics]);
    }
}
?>