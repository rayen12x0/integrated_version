<?php
// controllers/resourceController.php
// Simple resource Controller to handle business logic

require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../model/resource.php";
require_once __DIR__ . "/../utils/imageUpload.php";
require_once __DIR__ . "/../utils/AuthHelper.php";
require_once __DIR__ . "/../utils/CountryNameMapper.php";
require_once __DIR__ . "/../utils/ApiResponse.php";

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
                    ApiResponse::error("Missing required field: $field", 400);
                    return;
                }
            }

            // Normalize and validate country if provided
            if (isset($input['country']) && !empty(trim($input['country']))) {
                $input['country'] = CountryNameMapper::normalizeCountryName($input['country']);
                error_log("Normalized country for resource: " . $input['country']);

                // Validate that when coordinates are provided, country is not empty after normalization
                if (!empty($input['latitude']) && !empty($input['longitude']) && empty($input['country'])) {
                    ApiResponse::error("Country field is required when coordinates are provided", 400);
                    return;
                }
            } else {
                // If no country provided but coordinates exist, return error
                if (!empty($input['latitude']) && !empty($input['longitude'])) {
                    ApiResponse::error("Country field is required when coordinates are provided", 400);
                    return;
                }
                error_log("No country provided for resource");
            }

            // Handle resource name field - it might be called differently in various forms
            if (empty($input['resource_name']) && empty($input['title']) && empty($input['resourceName'])) {
                error_log("Missing required field: resource_name (could be named 'resource_name', 'title', or 'resourceName')");
                ApiResponse::error("Missing required field: resource_name (could be named 'resource_name', 'title', or 'resourceName')", 400);
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
                require_once __DIR__ . "/../model/notification.php"; // Include notification model
                $notification = new Notification($this->pdo); // Initialize notification model

                // Success response
                $lastId = $this->pdo->lastInsertId();
                error_log("resource created successfully with ID: " . $lastId);

                // Create notification for resource creation
                $notification->createResourceCreatedNotification($input['publisher_id'], $lastId, $input['resource_name']);

                ApiResponse::success(['id' => $lastId], 'resource created successfully', 201);
            } else {
                // Error response
                error_log("Failed to create resource in model.");
                ApiResponse::error("Failed to create resource", 400);
            }
        } catch (Exception $e) {
            error_log("Error in create resource: " . $e->getMessage());
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    // Get all resources method
    public function getAll() {
        try {
            $resources = $this->resource->getAll();
            // Return the data for the API endpoint to format
            return $resources;
        } catch (Exception $e) {
            // Throw exception for the API endpoint to handle
            throw $e;
        }
    }

    // Get resource by ID method
    public function getById($id) {
        header("Content-Type: application/json");

        try {
            $resource = $this->resource->findById($id);
            if ($resource) {
                ApiResponse::success($resource, 'Resource retrieved successfully', 200);
            } else {
                ApiResponse::error('Resource not found', 404);
            }
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    // Update resource method
    public function update() {
        header("Content-Type: application/json");

        try {
            // Get input data (handles both JSON and form data)
            $input = $this->getResourceData();

            if (!$input || !isset($input['id'])) {
                ApiResponse::error("Invalid input or missing resource ID", 400);
                return;
            }

            $id = $input['id'];
            unset($input['id']); // Remove ID from data to be updated

            // Verify ownership or admin role
            $currentUser = AuthHelper::getCurrentUser();
            $existingResource = $this->resource->findById($id);

            if (!$existingResource) {
                ApiResponse::error("Resource not found", 404);
                return;
            }

            if (!AuthHelper::isAdmin($currentUser) && !AuthHelper::isOwner($existingResource['publisher_id'], $currentUser['id'])) {
                ApiResponse::error("Unauthorized. You can only edit your own resources.", 403);
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
                        ApiResponse::error("Missing required field for update: $field", 400);
                        return;
                    }
                }
            }

            // Normalize and validate country if provided in input
            if (isset($input['country']) && !empty(trim($input['country']))) {
                $input['country'] = CountryNameMapper::normalizeCountryName($input['country']);
                error_log("Normalized country for resource update: " . $input['country']);

                // Validate that when coordinates are provided, country is not empty after normalization
                if (!empty($input['latitude']) && !empty($input['longitude']) && empty($input['country'])) {
                    ApiResponse::error("Country field is required when coordinates are provided", 400);
                    return;
                }
            } else {
                // If no country provided but coordinates exist, return error
                if (!empty($input['latitude']) && !empty($input['longitude'])) {
                    ApiResponse::error("Country field is required when coordinates are provided", 400);
                    return;
                }
            }

            // Set image_url if not provided (for backward compatibility)
            if (!isset($input['image_url'])) {
                $input['image_url'] = 'https://via.placeholder.com/400x200?text=Resource+Image';
            }

            // Get the existing resource before update to get the resource_name
            $existingResource = $this->resource->findById($id);

            $result = $this->resource->update($id, $input);

            if ($result) {
                require_once __DIR__ . "/../model/notification.php"; // Include notification model
                $notification = new Notification($this->pdo); // Initialize notification model

                // Create notification for resource update (only for the publisher)
                $currentUser = AuthHelper::getCurrentUser();
                if ($existingResource && $existingResource['publisher_id'] == $currentUser['id']) {
                    $resourceName = $input['resource_name'] ?? $existingResource['resource_name'];
                    $notification->createResourceUpdatedNotification($existingResource['publisher_id'], $id, $resourceName);
                }

                ApiResponse::success(null, 'resource updated successfully', 200);
            } else {
                ApiResponse::error("Failed to update resource or resource not found", 400);
            }
        } catch (Exception $e) {
            error_log("Error in update resource: " . $e->getMessage());
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    // Get resources by publisher ID method (for user dashboard)
    public function getByPublisherId($publisher_id) {
        try {
            $resources = $this->resource->getByPublisherId($publisher_id);
            // Return the data for the API endpoint to format
            return $resources;
        } catch (Exception $e) {
            // Throw exception for the API endpoint to handle
            throw $e;
        }
    }

    // Get approved resources method (for public display)
    public function getApproved() {
        try {
            $resources = $this->resource->getApproved();
            // Return the data for the API endpoint to format
            return $resources;
        } catch (Exception $e) {
            // Throw exception for the API endpoint to handle
            throw $e;
        }
    }

    // Approve resource method (for admin dashboard)
    public function approve() {
        header("Content-Type: application/json");
        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input || !isset($input['id']) || !isset($input['action'])) {
            ApiResponse::error("Invalid JSON input or missing resource ID or action", 400);
            return;
        }

        $id = $input['id'];
        $action = $input['action']; // 'approve' or 'reject'

        if ($action === 'approve') {
            // Get the existing resource before approve to get the resource_name and publisher_id
            $existingResource = $this->resource->findById($id);

            $result = $this->resource->approve($id);
            $message = $result ? "Resource approved successfully" : "Failed to approve resource";

            if ($result && $existingResource) {
                require_once __DIR__ . "/../model/notification.php"; // Include notification model
                $notification = new Notification($this->pdo); // Initialize notification model

                // Create notification for resource approval
                $notification->createResourceApprovedNotification($existingResource['publisher_id'], $existingResource['id'], $existingResource['resource_name']);
            }
        } elseif ($action === 'reject') {
            // Get the existing resource before reject to get the resource_name and publisher_id
            $existingResource = $this->resource->findById($id);

            $result = $this->resource->reject($id);
            $message = $result ? "Resource rejected successfully" : "Failed to reject resource";

            if ($result && $existingResource) {
                require_once __DIR__ . "/../model/notification.php"; // Include notification model
                $notification = new Notification($this->pdo); // Initialize notification model

                // Create notification for resource rejection
                $notification->createResourceRejectedNotification($existingResource['publisher_id'], $existingResource['id'], $existingResource['resource_name']);
            }
        } else {
            ApiResponse::error("Invalid action. Use 'approve' or 'reject'.", 400);
            return;
        }

        if ($result) {
            ApiResponse::success(null, $message, 200);
        } else {
            ApiResponse::error($message, 400);
        }
    }

    // Delete resource method
    public function delete() {
        header("Content-Type: application/json");
        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input || !isset($input['id'])) {
            ApiResponse::error("Invalid JSON input or missing resource ID", 400);
            return;
        }

        $id = $input['id'];

        // Verify ownership or admin role
        $currentUser = AuthHelper::getCurrentUser();
        $existingResource = $this->resource->findById($id);

        if (!$existingResource) {
            ApiResponse::error("Resource not found", 404);
            return;
        }

        if (!AuthHelper::isAdmin($currentUser) && !AuthHelper::isOwner($existingResource['publisher_id'], $currentUser['id'])) {
            ApiResponse::error("Unauthorized. You can only delete your own resources.", 403);
            return;
        }

        // Get the existing resource before deletion to get the resource_name and publisher_id
        $existingResource = $this->resource->findById($id);

        $result = $this->resource->delete($id);

        if ($result) {
            require_once __DIR__ . "/../model/notification.php"; // Include notification model
            $notification = new Notification($this->pdo); // Initialize notification model

            // Create notification for resource deletion
            if ($existingResource) {
                $notification->createResourceDeletedNotification($existingResource['publisher_id'], $existingResource['resource_name']);
            }

            ApiResponse::success(null, "resource deleted successfully", 200);
        } else {
            ApiResponse::error("Failed to delete resource or resource not found", 400);
        }
    }
}