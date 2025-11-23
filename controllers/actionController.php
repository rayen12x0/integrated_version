<?php
// controllers/ActionController.php
// Simple Action Controller to handle business logic

require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../model/action.php";
require_once __DIR__ . "/../utils/imageUpload.php";
require_once __DIR__ . "/../utils/AuthHelper.php";
require_once __DIR__ . "/../utils/CountryNameMapper.php";

class ActionController
{
    private $pdo;
    private $action;

    // Constructor initializes database connection and action model
    public function __construct() {
        // Add CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        $this->pdo = Config::getConnexion();
        $this->action = new Action($this->pdo);
    }

    // Handle input for both JSON and file uploads
    private function getActionData() {
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
            
            $data = [];
            foreach ($_POST as $key => $value) {
                $data[$key] = $value;
            }

            // Process image upload if present
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = ImageUpload::uploadImage($_FILES['image'], 'actions');
                if ($uploadResult['success']) {
                    $data['image_url'] = $uploadResult['image_url'];
                } else {
                    throw new Exception($uploadResult['message']);
                }
            }

            return $data;
        } else {
            // Handle JSON input
            $input = json_decode(file_get_contents("php://input"), true);
            if (!$input) {
                throw new Exception("Invalid JSON input");
            }
            return $input;
        }
    }

    // Create action method
    public function create() {
        error_log("ActionController::create() called.");
       
        header("Content-Type: application/json");

        try {
            // Get input data (handles both JSON and form data)
            $input = $this->getActionData();
            error_log("Received input: " . print_r($input, true));

            // Calculate end_time from duration if provided
            if (isset($input['actionDuration']) && isset($input['start_time'])) {
                $startTime = new DateTime($input['start_time']);
                $duration = (int)$input['actionDuration'];
                $startTime->add(new DateInterval('PT' . $duration . 'H'));
                $input['end_time'] = $startTime->format('Y-m-d H:i:s');
            }

            
            // Define required fields (excluding country as it may be auto-detected from coordinates)
            $required = ["creator_id", "title", "description", "category", "theme", "location", "start_time", "end_time"];

            // Check required fields but allow empty country
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    error_log("Missing required field: $field");
                    echo json_encode([
                        "success" => false,
                        "message" => "Missing required field: $field"
                    ]);
                    return;
                }
            }

            // Normalize and validate country if provided
            if (isset($input['country']) && !empty(trim($input['country']))) {
                $input['country'] = CountryNameMapper::normalizeCountryName($input['country']);
                error_log("Normalized country for action: " . $input['country']);

                // Validate that when coordinates are provided, country is not empty after normalization
                if (!empty($input['latitude']) && !empty($input['longitude']) && empty($input['country'])) {
                    echo json_encode([
                        "success" => false,
                        "message" => "Country field is required when coordinates are provided"
                    ]);
                    return;
                }
            } else {
                // If no country provided but coordinates exist, try to extract from location or return error
                if (!empty($input['latitude']) && !empty($input['longitude'])) {
                    echo json_encode([
                        "success" => false,
                        "message" => "Country field is required when coordinates are provided"
                    ]);
                    return;
                }
                error_log("No country provided for action");
            }

            // Create record in database
            error_log("Attempting to create action with data: " . print_r($input, true));
            $result = $this->action->create($input);

            if ($result) {
                require_once __DIR__ . "/../model/notification.php"; // Include notification model
                $notification = new Notification($this->pdo); // Initialize notification model

                // Success response
                $lastId = $this->pdo->lastInsertId();
                error_log("Action created successfully with ID: " . $lastId);

                // Create notification for action creation
                $notification->createActionCreatedNotification($input['creator_id'], $lastId, $input['title']);

                echo json_encode([
                    "success" => true,
                    "message" => "Action created successfully",
                    "id" => $lastId
                ]);
            } else {
                // Error response
                error_log("Failed to create action in model.");
                echo json_encode([
                    "success" => false,
                    "message" => "Failed to create action"
                ]);
            }
        } catch (Exception $e) {
            error_log("Error in create action: " . $e->getMessage());
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    // Get action by ID method
    public function getById($id) {
        header("Content-Type: application/json");

        try {
            $action = $this->action->findById($id);
            if ($action) {
                echo json_encode([
                    "success" => true,
                    "action" => $action
                ]);
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Action not found"
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    // Get actions by creator ID method (for user dashboard)
    public function getByCreatorId($creator_id) {
        header("Content-Type: application/json");

        try {
            $actions = $this->action->getByCreatorId($creator_id);
            echo json_encode([
                "success" => true,
                "actions" => $actions
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    // Get approved actions method (for public display)
    public function getApproved() {
        header("Content-Type: application/json");

        try {
            $actions = $this->action->getApproved();
            echo json_encode([
                "success" => true,
                "actions" => $actions
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    // Get all actions method
    public function getAll() {
        header("Content-Type: application/json");

        try {
            $actions = $this->action->getAll();
            echo json_encode([
                "success" => true,
                "actions" => $actions
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    // Update action method
    public function update() {
        header("Content-Type: application/json");

        try {
            // Get input data (handles both JSON and form data)
            $input = $this->getActionData();

            if (!$input || !isset($input['id'])) {
                echo json_encode([
                    "success" => false,
                    "message" => "Invalid input or missing action ID"
                ]);
                return;
            }

            $id = $input['id'];
            unset($input['id']); // Remove ID from data to be updated

            // Calculate end_time from duration if provided
            if (isset($input['actionDuration']) && isset($input['start_time'])) {
                $startTime = new DateTime($input['start_time']);
                $duration = (int)$input['actionDuration'];
                $startTime->add(new DateInterval('PT' . $duration . 'H'));
                $input['end_time'] = $startTime->format('Y-m-d H:i:s');
            }

            // Verify ownership or admin role
            $currentUser = AuthHelper::getCurrentUser();
            $existingAction = $this->action->findById($id);

            if (!$existingAction) {
                echo json_encode(["success" => false, "message" => "Action not found"]);
                return;
            }

            if (!AuthHelper::isAdmin($currentUser) && !AuthHelper::isOwner($existingAction['creator_id'], $currentUser['id'])) {
                http_response_code(403);
                echo json_encode(["success" => false, "message" => "Unauthorized. You can only edit your own actions."]);
                return;
            }

            // Required fields for action update (excluding country as it can be updated separately)
            // For now, let's assume all fields are required for a full update
            $required = ["creator_id", "title", "description", "category", "location", "start_time", "end_time"];
            foreach ($required as $field) {
                if (!isset($input[$field])) { // Use isset for updates, as fields might be intentionally empty
                    // For testing purposes, if creator_id is missing, set a default value
                    if ($field === "creator_id" && !isset($input[$field])) {
                        $input['creator_id'] = 1; // Default test user
                    } else {
                        echo json_encode([
                            "success" => false,
                            "message" => "Missing required field for update: $field"
                        ]);
                        return;
                    }
                }
            }

            // Normalize and validate country if provided in input
            if (isset($input['country']) && !empty(trim($input['country']))) {
                $input['country'] = CountryNameMapper::normalizeCountryName($input['country']);
                error_log("Normalized country for action update: " . $input['country']);

                // Validate that when coordinates are provided, country is not empty after normalization
                if (!empty($input['latitude']) && !empty($input['longitude']) && empty($input['country'])) {
                    echo json_encode([
                        "success" => false,
                        "message" => "Country field is required when coordinates are provided"
                    ]);
                    return;
                }
            } else {
                // If no country provided but coordinates exist, return error
                if (!empty($input['latitude']) && !empty($input['longitude'])) {
                    echo json_encode([
                        "success" => false,
                        "message" => "Country field is required when coordinates are provided"
                    ]);
                    return;
                }
            }

            // Get the existing action before update to get the title
            $existingAction = $this->action->findById($id);

            $result = $this->action->update($id, $input);

            if ($result) {
                require_once __DIR__ . "/../model/notification.php"; // Include notification model
                $notification = new Notification($this->pdo); // Initialize notification model

                // Create notification for action update (only for the creator)
                $currentUser = AuthHelper::getCurrentUser();
                if ($existingAction && $existingAction['creator_id'] == $currentUser['id']) {
                    $notification->createActionUpdatedNotification($existingAction['creator_id'], $id, $input['title'] ?? $existingAction['title']);
                }

                echo json_encode([
                    "success" => true,
                    "message" => "Action updated successfully"
                ]);
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Failed to update action or action not found"
                ]);
            }
        } catch (Exception $e) {
            error_log("Error in update action: " . $e->getMessage());
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    // Approve action method (for admin dashboard)
    public function approve() {
        header("Content-Type: application/json");
        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input || !isset($input['id']) || !isset($input['action'])) {
            echo json_encode([
                "success" => false,
                "message" => "Invalid JSON input or missing action ID or action"
            ]);
            return;
        }

        $id = $input['id'];
        $action = $input['action']; // 'approve' or 'reject'

        if ($action === 'approve') {
            // Get the existing action before approve to get the title and creator_id
            $existingAction = $this->action->findById($id);

            $result = $this->action->approve($id);
            $message = $result ? "Action approved successfully" : "Failed to approve action";

            if ($result && $existingAction) {
                require_once __DIR__ . "/../model/notification.php"; // Include notification model
                $notification = new Notification($this->pdo); // Initialize notification model

                // Create notification for action approval
                $notification->createActionApprovedNotification($existingAction['creator_id'], $existingAction['id'], $existingAction['title']);
            }
        } elseif ($action === 'reject') {
            // Get the existing action before reject to get the title and creator_id
            $existingAction = $this->action->findById($id);

            $result = $this->action->reject($id);
            $message = $result ? "Action rejected successfully" : "Failed to reject action";

            if ($result && $existingAction) {
                require_once __DIR__ . "/../model/notification.php"; // Include notification model
                $notification = new Notification($this->pdo); // Initialize notification model

                // Create notification for action rejection
                $notification->createActionRejectedNotification($existingAction['creator_id'], $existingAction['id'], $existingAction['title']);
            }
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Invalid action. Use 'approve' or 'reject'."
            ]);
            return;
        }

        if ($result) {
            echo json_encode([
                "success" => true,
                "message" => $message
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => $message
            ]);
        }
    }

    // Delete action method
    public function delete() {
        header("Content-Type: application/json");
        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input || !isset($input['id'])) {
            echo json_encode([
                "success" => false,
                "message" => "Invalid JSON input or missing action ID"
            ]);
            return;
        }

        $id = $input['id'];

        // Verify ownership or admin role
        $currentUser = AuthHelper::getCurrentUser();
        $existingAction = $this->action->findById($id);

        if (!$existingAction) {
            echo json_encode(["success" => false, "message" => "Action not found"]);
            return;
        }

        if (!AuthHelper::isAdmin($currentUser) && !AuthHelper::isOwner($existingAction['creator_id'], $currentUser['id'])) {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Unauthorized. You can only delete your own actions."]);
            return;
        }

        // Get the existing action before deletion to get the title and creator_id
        $existingAction = $this->action->findById($id);

        $result = $this->action->delete($id);

        if ($result) {
            require_once __DIR__ . "/../model/notification.php"; // Include notification model
            $notification = new Notification($this->pdo); // Initialize notification model

            // Create notification for action deletion
            if ($existingAction) {
                $notification->createActionDeletedNotification($existingAction['creator_id'], $existingAction['title']);
            }

            echo json_encode([
                "success" => true,
                "message" => "Action deleted successfully"
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Failed to delete action or action not found"
            ]);
        }
    }
}