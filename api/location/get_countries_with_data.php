<?php
// API endpoint to get countries that have actions or resources
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

try {
    $action = new Action();
    $resource = new Resource();
    
    // Get PDO connection from config
    $pdo = Config::getConnexion();
    
    // Get aggregated action counts by country
    $actionCountSql = "SELECT country, COUNT(*) AS count FROM actions WHERE country IS NOT NULL AND country != '' AND status = 'approved' GROUP BY country";
    $actionCountStmt = $pdo->prepare($actionCountSql);
    $actionCountStmt->execute();
    $actionCounts = [];
    while ($row = $actionCountStmt->fetch(PDO::FETCH_ASSOC)) {
        $actionCounts[$row['country']] = (int)$row['count'];
    }

    // Get aggregated resource counts by country
    $resourceCountSql = "SELECT country, COUNT(*) AS count FROM resources WHERE country IS NOT NULL AND country != '' AND status = 'approved' GROUP BY country";
    $resourceCountStmt = $pdo->prepare($resourceCountSql);
    $resourceCountStmt->execute();
    $resourceCounts = [];
    while ($row = $resourceCountStmt->fetch(PDO::FETCH_ASSOC)) {
        $resourceCounts[$row['country']] = (int)$row['count'];
    }

    // Get all unique countries from both arrays
    $allCountries = array_unique(array_merge(array_keys($actionCounts), array_keys($resourceCounts)));

    // Build countries data using precomputed counts
    $countriesData = [];
    foreach ($allCountries as $country) {
        if (!empty($country)) {
            $actionCount = isset($actionCounts[$country]) ? $actionCounts[$country] : 0;
            $resourceCount = isset($resourceCounts[$country]) ? $resourceCounts[$country] : 0;

            $countriesData[] = [
                'name' => $country,
                'actions' => $actionCount,
                'resources' => $resourceCount,
                'total' => $actionCount + $resourceCount
            ];
        }
    }
    
    // Sort by total count (descending)
    usort($countriesData, function($a, $b) {
        return $b['total'] - $a['total'];
    });

    echo json_encode([
        'success' => true,
        'countries' => $countriesData,
        'count' => count($countriesData)
    ]);
} catch (Exception $e) {
    error_log('Error getting countries with data: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving countries: ' . $e->getMessage()
    ]);
}
?>
