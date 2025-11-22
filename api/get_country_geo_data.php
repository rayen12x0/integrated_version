<?php
// API endpoint to get all actions and resources by country with location clustering
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

// Get country parameter
$country = $_GET['country'] ?? '';

if (empty($country)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Country parameter is required']);
    exit;
}

try {
    require_once '../config/config.php';
    $pdo = Config::getConnexion();
    
    // Get all approved actions in the country with location data
    $actionsSql = "SELECT id, title, description, category, location, country, 
                   latitude, longitude, start_time, participants, comment_count
                   FROM actions 
                   WHERE country = :country AND status = 'approved'
                   AND latitude IS NOT NULL AND longitude IS NOT NULL
                   ORDER BY created_at DESC";
    
    $actionsStmt = $pdo->prepare($actionsSql);
    $actionsStmt->bindParam(':country', $country, PDO::PARAM_STR);
    $actionsStmt->execute();
    $actions = $actionsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all approved resources in the country with location data
    $resourcesSql = "SELECT id, resource_name, description, type, category, location, country,
                     latitude, longitude, comment_count
                     FROM resources 
                     WHERE country = :country AND status = 'approved'
                     AND latitude IS NOT NULL AND longitude IS NOT NULL
                     ORDER BY created_at DESC";
    
    $resourcesStmt = $pdo->prepare($resourcesSql);
    $resourcesStmt->bindParam(':country', $country, PDO::PARAM_STR);
    $resourcesStmt->execute();
    $resources = $resourcesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare the response with geoJSON-like structure for mapping
    $geoData = [];
    
    foreach ($actions as $action) {
        $geoData[] = [
            'id' => $action['id'],
            'type' => 'action',
            'title' => $action['title'],
            'description' => $action['description'],
            'category' => $action['category'],
            'location' => $action['location'],
            'country' => $action['country'],
            'latitude' => (float)$action['latitude'],
            'longitude' => (float)$action['longitude'],
            'start_time' => $action['start_time'],
            'participants' => (int)$action['participants'],
            'comment_count' => (int)$action['comment_count']
        ];
    }
    
    foreach ($resources as $resource) {
        $geoData[] = [
            'id' => $resource['id'],
            'type' => 'resource',
            'title' => $resource['resource_name'],
            'description' => $resource['description'],
            'category' => $resource['category'],
            'type_name' => $resource['type'],
            'location' => $resource['location'],
            'country' => $resource['country'],
            'latitude' => (float)$resource['latitude'],
            'longitude' => (float)$resource['longitude'],
            'comment_count' => (int)$resource['comment_count']
        ];
    }
    
    // Sort by date (most recent first) - using start_time for actions and created_at for resources
    usort($geoData, function($a, $b) {
        $dateA = isset($a['start_time']) ? strtotime($a['start_time']) : strtotime($a['title'] . ' ' . $a['description']); // fallback for resources
        $dateB = isset($b['start_time']) ? strtotime($b['start_time']) : strtotime($b['title'] . ' ' . $b['description']); // fallback for resources
        return $dateB - $dateA;
    });

    echo json_encode([
        'success' => true,
        'country' => $country,
        'data' => $geoData,
        'total_count' => count($geoData),
        'actions_count' => count($actions),
        'resources_count' => count($resources)
    ]);
} catch (Exception $e) {
    error_log('Error getting country geo data: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving country geo data: ' . $e->getMessage()
    ]);
}
?>