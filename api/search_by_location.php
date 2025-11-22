<?php
// API endpoint to search for actions and resources by location/country
require_once '../config/config.php'; // Need this first to ensure Config class is available
require_once '../model/action.php';
require_once '../model/resource.php';
require_once '../utils/AuthHelper.php';

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

// Get parameters
$search = $_GET['search'] ?? '';
$country = $_GET['country'] ?? '';
$limit = (int)($_GET['limit'] ?? 20);
$offset = (int)($_GET['offset'] ?? 0);

// Validate parameters
if (empty($search) && empty($country)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Either search term or country must be provided']);
    exit;
}

try {
    require_once '../config/config.php';
    $pdo = Config::getConnexion();
    
    // Base query components
    $actionsWhere = [];
    $resourcesWhere = [];
    $params = [];
    
    // Add country filter if provided
    if (!empty($country)) {
        $actionsWhere[] = "country = :country";
        $resourcesWhere[] = "country = :country";
        $params[':country'] = $country;
    }
    
    // Add search filter if provided
    if (!empty($search)) {
        $searchPattern = '%' . $search . '%';
        $actionsWhere[] = "(title LIKE :search OR description LIKE :search OR location LIKE :search OR category LIKE :search)";
        $resourcesWhere[] = "(resource_name LIKE :search OR description LIKE :search OR location LIKE :search OR category LIKE :search OR type LIKE :search)";
        $params[':search'] = $searchPattern;
    }
    
    // Add status filter (only approved items)
    $actionsWhere[] = "status = 'approved'";
    $resourcesWhere[] = "status = 'approved'";
    
    // Build the final queries
    $actionsWhereClause = !empty($actionsWhere) ? 'WHERE ' . implode(' AND ', $actionsWhere) : '';
    $resourcesWhereClause = !empty($resourcesWhere) ? 'WHERE ' . implode(' AND ', $resourcesWhere) : '';
    
    // Get actions query
    $actionsSql = "SELECT id, title, description, category, theme, location, country,
                   latitude, longitude, start_time, end_time, status, image_url, 
                   created_at, participants, comment_count
                   FROM actions 
                   {$actionsWhereClause}
                   ORDER BY created_at DESC
                   LIMIT {$limit} OFFSET {$offset}";
    
    $actionsStmt = $pdo->prepare($actionsSql);
    $actionsStmt->execute($params);
    $actions = $actionsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get resources query
    $resourcesSql = "SELECT id, resource_name, description, type, category, location, country,
                     latitude, longitude, status, image_url,
                     created_at, comment_count
                     FROM resources 
                     {$resourcesWhereClause}
                     ORDER BY created_at DESC
                     LIMIT {$limit} OFFSET {$offset}";
    
    $resourcesStmt = $pdo->prepare($resourcesSql);
    $resourcesStmt->execute($params);
    $resources = $resourcesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total counts for pagination
    $actionsCountSql = "SELECT COUNT(*) as count FROM actions " . (!empty($actionsWhere) ? $actionsWhereClause : '');
    $actionsCountStmt = $pdo->prepare($actionsCountSql);
    $actionsCountStmt->execute($params);
    $actionsTotal = $actionsCountStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $resourcesCountSql = "SELECT COUNT(*) as count FROM resources " . (!empty($resourcesWhere) ? $resourcesWhereClause : '');
    $resourcesCountStmt = $pdo->prepare($resourcesCountSql);
    $resourcesCountStmt->execute($params);
    $resourcesTotal = $resourcesCountStmt->fetch(PDO::FETCH_ASSOC)['count'];

    echo json_encode([
        'success' => true,
        'search' => $search,
        'country' => $country,
        'actions' => $actions,
        'resources' => $resources,
        'counts' => [
            'actions' => (int)$actionsTotal,
            'resources' => (int)$resourcesTotal,
            'total' => (int)$actionsTotal + (int)$resourcesTotal
        ],
        'pagination' => [
            'limit' => $limit,
            'offset' => $offset,
            'next_offset' => $offset + $limit
        ]
    ]);
} catch (Exception $e) {
    error_log('Error in location search: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error performing location search: ' . $e->getMessage()
    ]);
}
?>