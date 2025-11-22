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
    require_once '../../config/config.php';
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all countries with approved actions
    $actionSql = "SELECT DISTINCT country FROM actions WHERE country IS NOT NULL AND country != '' AND status = 'approved'";
    $actionStmt = $pdo->prepare($actionSql);
    $actionStmt->execute();
    $actionCountries = $actionStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get all countries with approved resources
    $resourceSql = "SELECT DISTINCT country FROM resources WHERE country IS NOT NULL AND country != '' AND status = 'approved'";
    $resourceStmt = $pdo->prepare($resourceSql);
    $resourceStmt->execute();
    $resourceCountries = $resourceStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Merge and get unique countries
    $allCountries = array_unique(array_merge($actionCountries, $resourceCountries));
    
    // Count actions and resources for each country
    $countriesData = [];
    foreach ($allCountries as $country) {
        if (!empty($country)) {
            // Count actions in this country
            $actionCountSql = "SELECT COUNT(*) as count FROM actions WHERE country = :country AND status = 'approved'";
            $actionCountStmt = $pdo->prepare($actionCountSql);
            $actionCountStmt->bindParam(':country', $country, PDO::PARAM_STR);
            $actionCountStmt->execute();
            $actionCount = $actionCountStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Count resources in this country
            $resourceCountSql = "SELECT COUNT(*) as count FROM resources WHERE country = :country AND status = 'approved'";
            $resourceCountStmt = $pdo->prepare($resourceCountSql);
            $resourceCountStmt->bindParam(':country', $country, PDO::PARAM_STR);
            $resourceCountStmt->execute();
            $resourceCount = $resourceCountStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            $countriesData[] = [
                'name' => $country,
                'actions' => (int)$actionCount,
                'resources' => (int)$resourceCount,
                'total' => (int)$actionCount + (int)$resourceCount
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
