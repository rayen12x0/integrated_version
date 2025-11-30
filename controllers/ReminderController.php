<?php
// For the ReminderController, when included from API files,
// we need to account for the file path structure
// API files are in api/reminders/
// Controllers are in controllers/
// So from api/reminders/ to config/, model/, and utils/ is ../../

// Direct require paths from API context
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../model/reminder.php';
require_once __DIR__ . '/../utils/AuthHelper.php';

class ReminderController {
    private $conn;
    private $reminderModel;

    public function __construct() {
        $this->conn = Config::getConnexion();
        $this->reminderModel = new Reminder($this->conn);
    }

    public function create() {
        // Check if user is authenticated
        if (!AuthHelper::isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'User not authenticated']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $_SESSION['user_id'];

        // Validate required fields
        if (!isset($data['item_id']) || !isset($data['item_type']) || !isset($data['reminder_time'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }

        // Set default values
        $reminderType = isset($data['reminder_type']) ? $data['reminder_type'] : 'both';

        // Prepare data for model
        $reminderData = [
            'user_id' => $userId,
            'item_id' => $data['item_id'],
            'item_type' => $data['item_type'],
            'reminder_type' => $reminderType,
            'reminder_time' => $data['reminder_time']
        ];

        $result = $this->reminderModel->create($reminderData);
        echo json_encode($result);
    }

    public function getByUser() {
        // Check if user is authenticated
        if (!AuthHelper::isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'User not authenticated']);
            return;
        }

        $userId = $_SESSION['user_id'];
        $reminders = $this->reminderModel->getByUser($userId);

        echo json_encode(['success' => true, 'data' => $reminders]);
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

        $reminders = $this->reminderModel->getByItem($itemId, $itemType);

        echo json_encode(['success' => true, 'data' => $reminders]);
    }

    public function delete() {
        // Check if user is authenticated
        if (!AuthHelper::isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'User not authenticated']);
            return;
        }

        $id = isset($_GET['id']) ? $_GET['id'] : null;
        $userId = $_SESSION['user_id'];

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Missing reminder ID']);
            return;
        }

        // Check if user owns the reminder
        $reminders = $this->reminderModel->getByUser($userId);
        $reminderExists = false;
        foreach ($reminders as $reminder) {
            if ($reminder['id'] == $id) {
                $reminderExists = true;
                break;
            }
        }

        if (!$reminderExists) {
            echo json_encode(['success' => false, 'message' => 'Access denied - you do not own this reminder']);
            return;
        }

        $result = $this->reminderModel->delete($id);
        echo json_encode($result);
    }

    public function processDueReminders() {
        // This method is for internal use by the cron job script
        $dueReminders = $this->reminderModel->getDueReminders();
        return $dueReminders;
    }
}
?>