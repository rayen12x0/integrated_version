<?php
// API endpoint to delete a notification
// This file connects the frontend to the controller with enhanced validation

require_once "../../utils/AuthHelper.php";
require_once "../../config/config.php";
require_once "../../model/notification.php";

header("Content-Type: application/json");
// Add CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Get current user
$currentUser = AuthHelper::getCurrentUser();

// Check if user is authenticated
if (!$currentUser || $currentUser['id'] === null) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized: User not authenticated"
    ]);
    exit;
}

// Get input data
$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON input"
    ]);
    exit;
}

// Extract IDs - support both single ID and array of IDs
$ids = [];
if (isset($input['id']) && is_numeric($input['id'])) {
    $ids = [(int)$input['id']];
} elseif (isset($input['ids']) && is_array($input['ids'])) {
    $ids = array_map('intval', $input['ids']);
} else {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Missing notification ID(s). Use 'id' for single notification or 'ids' for multiple notifications."
    ]);
    exit;
}

if (empty($ids)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "No valid notification IDs provided"
    ]);
    exit;
}

// Validate that all notifications belong to the current user and exist
$pdo = Config::getConnexion();
$notificationModel = new Notification($pdo);

foreach ($ids as $id) {
    // Get notification to verify ownership and existence
    $stmt = $pdo->prepare("SELECT user_id FROM notifications WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $notification = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$notification) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Notification with ID $id not found"
        ]);
        exit;
    }

    if ($notification['user_id'] != $currentUser['id']) {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "message" => "Forbidden: Cannot delete notification that does not belong to you"
        ]);
        exit;
    }
}

// All validations passed, proceed with deletion
$deletedCount = 0;
$errorMessage = null;

foreach ($ids as $id) {
    try {
        $result = $notificationModel->delete($id);
        if ($result) {
            $deletedCount++;
        } else {
            $errorMessage = "Failed to delete notification with ID $id";
            break;
        }
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
        break;
    }
}

if ($errorMessage) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error deleting notification(s): " . $errorMessage
    ]);
} else {
    echo json_encode([
        "success" => true,
        "message" => "$deletedCount notification(s) deleted successfully",
        "count" => $deletedCount
    ]);
}
