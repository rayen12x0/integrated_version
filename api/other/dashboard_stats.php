<?php
// API endpoint to get dashboard statistics
error_reporting(0); // Suppress PHP errors that may cause JSON parsing issues
ini_set('display_errors', 0);

require_once "../../config/config.php";

header("Content-Type: application/json");

// Clear any output buffers
if (ob_get_level()) {
    ob_clean();
}

try {
    $pdo = Config::getConnexion();

    $userId = $_GET['user_id'] ?? null;

    if (!$userId) {
        echo json_encode([
            "success" => false,
            "message" => "User ID is required"
        ]);
        exit;
    }

    // Query the actions table to count rows where creator_id = userId
    $actionsQuery = "SELECT COUNT(*) as total FROM actions WHERE creator_id = :user_id";
    $actionsStmt = $pdo->prepare($actionsQuery);
    $actionsStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $actionsStmt->execute();
    $actionsCount = $actionsStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Query the resources table to count rows where publisher_id = userId
    $resourcesQuery = "SELECT COUNT(*) as total FROM resources WHERE publisher_id = :user_id";
    $resourcesStmt = $pdo->prepare($resourcesQuery);
    $resourcesStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $resourcesStmt->execute();
    $resourcesCount = $resourcesStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Check if getting participation count specifically
    if (isset($_GET['get_participation']) && $_GET['get_participation'] === 'true') {
        // Query the action_participants table to count rows where user_id = userId
        $participatedQuery = "SELECT COUNT(*) as total FROM action_participants WHERE user_id = :user_id";
        $participatedStmt = $pdo->prepare($participatedQuery);
        $participatedStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $participatedStmt->execute();
        $participatedCount = $participatedStmt->fetch(PDO::FETCH_ASSOC)['total'];

        echo json_encode([
            "success" => true,
            "count" => $participatedCount
        ]);
        exit;
    }

    // Query the action_participants table to count rows where user_id = userId
    $participatedQuery = "SELECT COUNT(*) as total FROM action_participants WHERE user_id = :user_id";
    $participatedStmt = $pdo->prepare($participatedQuery);
    $participatedStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $participatedStmt->execute();
    $participatedCount = $participatedStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Query the comments table to count rows where user_id = userId
    $commentsQuery = "SELECT COUNT(*) as total FROM comments WHERE user_id = :user_id";
    $commentsStmt = $pdo->prepare($commentsQuery);
    $commentsStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $commentsStmt->execute();
    $commentsCount = $commentsStmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo json_encode([
        "success" => true,
        "total_actions" => (int)$actionsCount,
        "total_resources" => (int)$resourcesCount,
        "count" => (int)$participatedCount,
        "comments_count" => (int)$commentsCount
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error retrieving dashboard stats: " . $e->getMessage()
    ]);
}

// Make sure no extra output after JSON
exit;
