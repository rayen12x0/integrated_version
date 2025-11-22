<?php
// API endpoint to get comprehensive statistics for a specific country
require_once '../../config/config.php'; // Need this first to ensure Config class is available
require_once '../../model/action.php';
require_once '../../model/resource.php';
require_once '../../utils/AuthHelper.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Check if request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get country parameter
$country = $_GET['country'] ?? '';

if (empty($country)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Country parameter is required']);
    exit;
}

try {
    $pdo = Config::getConnexion();
    
    // Get action statistics for this country
    $actionStatsSql = "SELECT 
                        COUNT(*) as total_actions,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_actions,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_actions,
                        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_actions
                      FROM actions 
                      WHERE country = :country";
    
    $actionStatsStmt = $pdo->prepare($actionStatsSql);
    $actionStatsStmt->bindParam(':country', $country, PDO::PARAM_STR);
    $actionStatsStmt->execute();
    $actionStats = $actionStatsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get resource statistics for this country
    $resourceStatsSql = "SELECT 
                         COUNT(*) as total_resources,
                         SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_resources,
                         SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_resources,
                         SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_resources
                       FROM resources 
                       WHERE country = :country";
    
    $resourceStatsStmt = $pdo->prepare($resourceStatsSql);
    $resourceStatsStmt->bindParam(':country', $country, PDO::PARAM_STR);
    $resourceStatsStmt->execute();
    $resourceStats = $resourceStatsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get top categories for actions in this country
    $actionCategorySql = "SELECT category, COUNT(*) as count 
                         FROM actions 
                         WHERE country = :country AND status = 'approved' AND category IS NOT NULL AND category != ''
                         GROUP BY category 
                         ORDER BY count DESC 
                         LIMIT 5";
    
    $actionCategoryStmt = $pdo->prepare($actionCategorySql);
    $actionCategoryStmt->bindParam(':country', $country, PDO::PARAM_STR);
    $actionCategoryStmt->execute();
    $actionCategories = $actionCategoryStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get top categories for resources in this country
    $resourceCategorySql = "SELECT category, COUNT(*) as count 
                           FROM resources 
                           WHERE country = :country AND status = 'approved' AND category IS NOT NULL AND category != ''
                           GROUP BY category 
                           ORDER BY count DESC 
                           LIMIT 5";
    
    $resourceCategoryStmt = $pdo->prepare($resourceCategorySql);
    $resourceCategoryStmt->bindParam(':country', $country, PDO::PARAM_STR);
    $resourceCategoryStmt->execute();
    $resourceCategories = $resourceCategoryStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total participants for actions in this country
    $totalParticipantsSql = "SELECT COALESCE(SUM(aparticipants.count), 0) as total_participants
                             FROM actions a
                             LEFT JOIN (
                               SELECT action_id, COUNT(*) as count 
                               FROM action_participants 
                               GROUP BY action_id
                             ) aparticipants ON a.id = aparticipants.action_id
                             WHERE a.country = :country AND a.status = 'approved'";
    
    $totalParticipantsStmt = $pdo->prepare($totalParticipantsSql);
    $totalParticipantsStmt->bindParam(':country', $country, PDO::PARAM_STR);
    $totalParticipantsStmt->execute();
    $totalParticipants = $totalParticipantsStmt->fetch(PDO::FETCH_ASSOC)['total_participants'];
    
    $statistics = [
        'country' => $country,
        'actions' => [
            'total' => (int)$actionStats['total_actions'],
            'approved' => (int)$actionStats['approved_actions'],
            'pending' => (int)$actionStats['pending_actions'],
            'rejected' => (int)$actionStats['rejected_actions'],
        ],
        'resources' => [
            'total' => (int)$resourceStats['total_resources'],
            'approved' => (int)$resourceStats['approved_resources'],
            'pending' => (int)$resourceStats['pending_resources'],
            'rejected' => (int)$resourceStats['rejected_resources'],
        ],
        'totals' => [
            'actions_and_resources' => (int)$actionStats['total_actions'] + (int)$resourceStats['total_resources'],
            'approved_actions_and_resources' => (int)$actionStats['approved_actions'] + (int)$resourceStats['approved_resources'],
            'total_participants' => (int)$totalParticipants
        ],
        'top_action_categories' => $actionCategories,
        'top_resource_categories' => $resourceCategories
    ];

    echo json_encode([
        'success' => true,
        'statistics' => $statistics
    ]);
} catch (Exception $e) {
    error_log('Error getting country statistics: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving country statistics: ' . $e->getMessage()
    ]);
}
?>
