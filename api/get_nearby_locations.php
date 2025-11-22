<?php
// API endpoint to get nearby actions and resources based on coordinates
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
$lat = $_GET['lat'] ?? null;
$lng = $_GET['lng'] ?? null;
$radius = (float)($_GET['radius'] ?? 100); // radius in kilometers
$limit = (int)($_GET['limit'] ?? 20);

// Validate parameters
if ($lat === null || $lng === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Latitude and longitude are required']);
    exit;
}

if (!is_numeric($lat) || !is_numeric($lng) || abs($lat) > 90 || abs($lng) > 180) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid latitude or longitude']);
    exit;
}

try {
    require_once '../config/config.php';
    $pdo = Config::getConnexion();
    
    // Calculate bounding box for initial filtering (approximate)
    $latRange = $radius / 111.045; // 1 degree is approximately 111 km
    $lngRange = $radius / (111.045 * cos(deg2rad($lat)));
    
    // Get nearby actions using Haversine formula
    $actionsSql = "SELECT *, 
                   (6371 * acos(cos(radians(:lat)) * cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(:lng)) + 
                    sin(radians(:lat)) * sin(radians(latitude)))) AS distance
                   FROM actions
                   WHERE status = 'approved'
                   AND latitude IS NOT NULL AND longitude IS NOT NULL
                   AND latitude BETWEEN :lat - :latRange AND :lat + :latRange
                   AND longitude BETWEEN :lng - :lngRange AND :lng + :lngRange
                   HAVING distance < :radius
                   ORDER BY distance
                   LIMIT :limit";
    
    $actionsStmt = $pdo->prepare($actionsSql);
    $actionsStmt->bindParam(':lat', $lat, PDO::PARAM_STR);
    $actionsStmt->bindParam(':lng', $lng, PDO::PARAM_STR);
    $actionsStmt->bindParam(':latRange', $latRange, PDO::PARAM_STR);
    $actionsStmt->bindParam(':lngRange', $lngRange, PDO::PARAM_STR);
    $actionsStmt->bindParam(':radius', $radius, PDO::PARAM_STR);
    $actionsStmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $actionsStmt->execute();
    $actions = $actionsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get nearby resources using Haversine formula
    $resourcesSql = "SELECT *, 
                     (6371 * acos(cos(radians(:lat)) * cos(radians(latitude)) * 
                      cos(radians(longitude) - radians(:lng)) + 
                      sin(radians(:lat)) * sin(radians(latitude)))) AS distance
                     FROM resources
                     WHERE status = 'approved'
                     AND latitude IS NOT NULL AND longitude IS NOT NULL
                     AND latitude BETWEEN :lat2 - :latRange2 AND :lat2 + :latRange2
                     AND longitude BETWEEN :lng2 - :lngRange2 AND :lng2 + :lngRange2
                     HAVING distance < :radius2
                     ORDER BY distance
                     LIMIT :limit2";
    
    $resourcesStmt = $pdo->prepare($resourcesSql);
    $resourcesStmt->bindParam(':lat2', $lat, PDO::PARAM_STR);
    $resourcesStmt->bindParam(':lng2', $lng, PDO::PARAM_STR);
    $resourcesStmt->bindParam(':latRange2', $latRange, PDO::PARAM_STR);
    $resourcesStmt->bindParam(':lngRange2', $lngRange, PDO::PARAM_STR);
    $resourcesStmt->bindParam(':radius2', $radius, PDO::PARAM_STR);
    $resourcesStmt->bindParam(':limit2', $limit, PDO::PARAM_INT);
    $resourcesStmt->execute();
    $resources = $resourcesStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'center' => ['lat' => (float)$lat, 'lng' => (float)$lng],
        'radius_km' => $radius,
        'actions' => $actions,
        'resources' => $resources,
        'counts' => [
            'actions' => count($actions),
            'resources' => count($resources),
            'total' => count($actions) + count($resources)
        ]
    ]);
} catch (Exception $e) {
    error_log('Error getting nearby locations: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving nearby locations: ' . $e->getMessage()
    ]);
}
?>