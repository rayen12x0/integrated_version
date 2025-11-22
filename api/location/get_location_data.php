<?php
// API endpoint to get comprehensive location data (country or nearby based on coordinates)
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

// Get parameters
$country = $_GET['country'] ?? '';
$lat = $_GET['lat'] ?? null;
$lng = $_GET['lng'] ?? null;
$radius = (float)($_GET['radius'] ?? 100); // radius in kilometers, default 100km
$limit = (int)($_GET['limit'] ?? 50); // total limit for both actions and resources

if (empty($country) && ($lat === null || $lng === null)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Either country parameter OR both latitude and longitude parameters must be provided'
    ]);
    exit;
}

try {
    require_once '../../config/config.php';
    $pdo = Config::getConnexion();
    
    $actions = [];
    $resources = [];
    
    if (!empty($country)) {
        // Get by country
        $actionsSql = "SELECT id, title, description, category, theme, location, country,
                       latitude, longitude, start_time, end_time, status, image_url, 
                       created_at, participants, comment_count
                       FROM actions 
                       WHERE country = :country AND status = 'approved'
                       AND latitude IS NOT NULL AND longitude IS NOT NULL
                       ORDER BY created_at DESC
                       LIMIT :limit";
        
        $actionsStmt = $pdo->prepare($actionsSql);
        $actionsStmt->bindParam(':country', $country, PDO::PARAM_STR);
        $actionsStmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $actionsStmt->execute();
        $actions = $actionsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $resourcesSql = "SELECT id, resource_name, description, type, category, location, country,
                         latitude, longitude, status, image_url,
                         created_at, comment_count
                         FROM resources 
                         WHERE country = :country AND status = 'approved'
                         AND latitude IS NOT NULL AND longitude IS NOT NULL
                         ORDER BY created_at DESC
                         LIMIT :limit";
        
        $resourcesStmt = $pdo->prepare($resourcesSql);
        $resourcesStmt->bindParam(':country', $country, PDO::PARAM_STR);
        $resourcesStmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $resourcesStmt->execute();
        $resources = $resourcesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response = [
            'success' => true,
            'type' => 'country',
            'country' => $country,
            'actions' => $actions,
            'resources' => $resources,
            'counts' => [
                'actions' => count($actions),
                'resources' => count($resources),
                'total' => count($actions) + count($resources)
            ]
        ];
    } else {
        // Get nearby based on coordinates
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

        $response = [
            'success' => true,
            'type' => 'nearby',
            'center' => ['lat' => (float)$lat, 'lng' => (float)$lng],
            'radius_km' => $radius,
            'actions' => $actions,
            'resources' => $resources,
            'counts' => [
                'actions' => count($actions),
                'resources' => count($resources),
                'total' => count($actions) + count($resources)
            ]
        ];
    }

    echo json_encode($response);
} catch (Exception $e) {
    error_log('Error getting location data: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving location data: ' . $e->getMessage()
    ]);
}
?>
