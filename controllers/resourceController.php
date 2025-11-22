<?php
// controllers/resourceController.php
// Simple resource Controller to handle business logic

require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../model/resource.php";
require_once __DIR__ . "/../utils/imageUpload.php";
require_once __DIR__ . "/../utils/AuthHelper.php";

class ResourceController
{
    private $pdo;
    private $resource;

    // Constructor initializes database connection and resource model
    public function __construct() {
        // Add CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        $this->pdo = Config::getConnexion();
        $this->resource = new Resource($this->pdo);
    }

    // Handle input for both JSON and file uploads
    private function getResourceData() {
        // Check if it's a file upload (multipart/form-data)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
            // Handle file upload data
            $data = [];
            foreach ($_POST as $key => $value) {
                $data[$key] = $value;
            }

            // Process image upload if present
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = ImageUpload::uploadImage($_FILES['image'], 'resources');
                if ($uploadResult['success']) {
                    $data['image_url'] = $uploadResult['image_url'];
                } else {
                    throw new Exception($uploadResult['message']);
                }
            }

            // For testing purposes, if publisher_id is missing, set a default value
            if (empty($data['publisher_id'])) {
                $data['publisher_id'] = 1; // Default test user
            }

            return $data;
        } else {
            // Handle JSON input
            $input = json_decode(file_get_contents("php://input"), true);
            if (!$input) {
                throw new Exception("Invalid JSON input");
            }

            // For testing purposes, if publisher_id is missing, set a default value
            if (empty($input['publisher_id'])) {
                $input['publisher_id'] = 1; // Default test user
            }

            return $input;
        }
    }

    // Create resource method
    public function create() {
        error_log("ResourceController::create() called.");
        // Set JSON header
        header("Content-Type: application/json");

        try {
            // Get input data (handles both JSON and form data)
            $input = $this->getResourceData();
            error_log("Received input: " . print_r($input, true));

            // Define required fields (excluding country as it may be auto-detected from coordinates)
            $required = ["publisher_id", "description", "category", "type", "location"];

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

            // Handle country field if provided in input
            if (isset($input['country'])) {
                error_log("Country provided: " . $input['country']);
            } else {
                error_log("No country provided, coordinates may be used for reverse geocoding later");
            }

            // Handle resource name field - it might be called differently in various forms
            if (empty($input['resource_name']) && empty($input['title']) && empty($input['resourceName'])) {
                error_log("Missing required field: resource_name (could be named 'resource_name', 'title', or 'resourceName')");
                echo json_encode([
                    "success" => false,
                    "message" => "Missing required field: resource_name (could be named 'resource_name', 'title', or 'resourceName')"
                ]);
                return;
            }

            // Normalize resource name field to 'resource_name' for the model
            if (empty($input['resource_name'])) {
                if (!empty($input['title'])) {
                    $input['resource_name'] = $input['title'];
                } elseif (!empty($input['resourceName'])) {
                    $input['resource_name'] = $input['resourceName'];
                } else {
                    $input['resource_name'] = 'Untitled Resource';
                }
            }

            // Add image to input, using a default if not provided
            if (!isset($input['image_url'])) {
                $input['image_url'] = 'https://via.placeholder.com/400x200?text=Resource+Image';
            }

            // Create record in database
            error_log("Attempting to create resource with data: " . print_r($input, true));
            $result = $this->resource->create($input);

            if ($result) {
                // Success response
                $lastId = $this->pdo->lastInsertId();
                error_log("resource created successfully with ID: " . $lastId);
                echo json_encode([
                    "success" => true,
                    "message" => "resource created successfully",
                    "id" => $lastId
                ]);
            } else {
                // Error response
                error_log("Failed to create resource in model.");
                echo json_encode([
                    "success" => false,
                    "message" => "Failed to create resource"
                ]);
            }
        } catch (Exception $e) {
            error_log("Error in create resource: " . $e->getMessage());
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    // Get all resources method
    public function getAll() {
        header("Content-Type: application/json");

        try {
            $resources = $this->resource->getAll();
            echo json_encode([
                "success" => true,
                "resources" => $resources
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    // Get resource by ID method
    public function getById($id) {
        header("Content-Type: application/json");

        try {
            $resource = $this->resource->findById($id);
            if ($resource) {
                echo json_encode([
                    "success" => true,
                    "resource" => $resource
                ]);
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Resource not found"
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    // Update resource method
    public function update() {
        header("Content-Type: application/json");

        try {
            // Get input data (handles both JSON and form data)
            $input = $this->getResourceData();

            if (!$input || !isset($input['id'])) {
                echo json_encode([
                    "success" => false,
                    "message" => "Invalid input or missing resource ID"
                ]);
                return;
            }

            $id = $input['id'];
            unset($input['id']); // Remove ID from data to be updated

            // Verify ownership or admin role
            $currentUser = AuthHelper::getCurrentUser();
            $existingResource = $this->resource->findById($id);

            if (!$existingResource) {
                echo json_encode(["success" => false, "message" => "Resource not found"]);
                return;
            }

            if (!AuthHelper::isAdmin($currentUser) && !AuthHelper::isOwner($existingResource['publisher_id'], $currentUser['id'])) {
                http_response_code(403);
                echo json_encode(["success" => false, "message" => "Unauthorized. You can only edit your own resources."]);
                return;
            }

            // Required fields for resource update (excluding country as it can be updated separately)
            $required = ["publisher_id", "resource_name", "description", "category", "type", "location"];
            foreach ($required as $field) {
                if (!isset($input[$field])) { // Use isset for updates, as fields might be intentionally empty
                    // For testing purposes, if publisher_id is missing, set a default value
                    if ($field === "publisher_id" && !isset($input[$field])) {
                        $input['publisher_id'] = 1; // Default test user
                    } else {
                        echo json_encode([
                            "success" => false,
                            "message" => "Missing required field for update: $field"
                        ]);
                        return;
                    }
                }
            }

            // Handle country field if provided in input
            if (isset($input['country'])) {
                error_log("Updating country field: " . $input['country']);
            }

            // Set image_url if not provided (for backward compatibility)
            if (!isset($input['image_url'])) {
                $input['image_url'] = 'https://via.placeholder.com/400x200?text=Resource+Image';
            }

            $result = $this->resource->update($id, $input);

            if ($result) {
                echo json_encode([
                    "success" => true,
                    "message" => "resource updated successfully"
                ]);
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Failed to update resource or resource not found"
                ]);
            }
        } catch (Exception $e) {
            error_log("Error in update resource: " . $e->getMessage());
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    // Get resources by publisher ID method (for user dashboard)
    public function getByPublisherId($publisher_id) {
        header("Content-Type: application/json");

        try {
            $resources = $this->resource->getByPublisherId($publisher_id);
            echo json_encode([
                "success" => true,
                "resources" => $resources
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    // Get approved resources method (for public display)
    public function getApproved() {
        header("Content-Type: application/json");

        try {
            $resources = $this->resource->getApproved();
            echo json_encode([
                "success" => true,
                "resources" => $resources
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    // Approve resource method (for admin dashboard)
    public function approve() {
        header("Content-Type: application/json");
        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input || !isset($input['id']) || !isset($input['action'])) {
            echo json_encode([
                "success" => false,
                "message" => "Invalid JSON input or missing resource ID or action"
            ]);
            return;
        }

        $id = $input['id'];
        $action = $input['action']; // 'approve' or 'reject'

        if ($action === 'approve') {
            $result = $this->resource->approve($id);
            $message = $result ? "Resource approved successfully" : "Failed to approve resource";
        } elseif ($action === 'reject') {
            $result = $this->resource->reject($id);
            $message = $result ? "Resource rejected successfully" : "Failed to reject resource";
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

    // Delete resource method
    public function delete() {
        header("Content-Type: application/json");
        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input || !isset($input['id'])) {
            echo json_encode([
                "success" => false,
                "message" => "Invalid JSON input or missing resource ID"
            ]);
            return;
        }

        $id = $input['id'];

        // Verify ownership or admin role
        $currentUser = AuthHelper::getCurrentUser();
        $existingResource = $this->resource->findById($id);

        if (!$existingResource) {
            echo json_encode(["success" => false, "message" => "Resource not found"]);
            return;
        }

        if (!AuthHelper::isAdmin($currentUser) && !AuthHelper::isOwner($existingResource['publisher_id'], $currentUser['id'])) {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Unauthorized. You can only delete your own resources."]);
            return;
        }

        $result = $this->resource->delete($id);

        if ($result) {
            echo json_encode([
                "success" => true,
                "message" => "resource deleted successfully"
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Failed to delete resource or resource not found"
            ]);
        }
    }
}