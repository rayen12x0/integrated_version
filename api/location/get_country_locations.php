<?php
// API endpoint to get all locations within a specific country
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
    require_once '../../config/config.php';
    $pdo = Config::getConnexion();
    
    // Get all unique locations with coordinates for actions in this country
    $actionLocationsSql = "SELECT DISTINCT location, latitude, longitude, 
                          COUNT(*) as count, 
                          GROUP_CONCAT(title SEPARATOR ' | ') as titles
                          FROM actions 
                          WHERE country = :country AND status = 'approved' 
                          AND latitude IS NOT NULL AND longitude IS NOT NULL
                          GROUP BY location, latitude, longitude";
    
    $actionLocationsStmt = $pdo->prepare($actionLocationsSql);
    $actionLocationsStmt->bindParam(':country', $country, PDO::PARAM_STR);
    $actionLocationsStmt->execute();
    $actionLocations = $actionLocationsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all unique locations with coordinates for resources in this country
    $resourceLocationsSql = "SELECT DISTINCT location, latitude, longitude, 
                            COUNT(*) as count, 
                            GROUP_CONCAT(resource_name SEPARATOR ' | ') as names
                            FROM resources 
                            WHERE country = :country AND status = 'approved' 
                            AND latitude IS NOT NULL AND longitude IS NOT NULL
                            GROUP BY location, latitude, longitude";
    
    $resourceLocationsStmt = $pdo->prepare($resourceLocationsSql);
    $resourceLocationsStmt->bindParam(':country', $country, PDO::PARAM_STR);
    $resourceLocationsStmt->execute();
    $resourceLocations = $resourceLocationsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine locations
    $allLocations = [];
    
    foreach ($actionLocations as $location) {
        $location['type'] = 'action';
        $location['titles'] = explode(' | ', $location['titles']);
        $allLocations[] = $location;
    }
    
    foreach ($resourceLocations as $location) {
        $location['type'] = 'resource';
        $location['names'] = explode(' | ', $location['names']);
        $allLocations[] = $location;
    }
    
    // Sort by count (descending)
    usort($allLocations, function($a, $b) {
        return $b['count'] - $a['count'];
    });

    echo json_encode([
        'success' => true,
        'country' => $country,
        'locations' => $allLocations,
        'count' => count($allLocations)
    ]);
} catch (Exception $e) {
    error_log('Error getting country locations: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving country locations: ' . $e->getMessage()
    ]);
}
?>
