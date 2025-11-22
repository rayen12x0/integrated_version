<?php
// Debug script to check country data loading
require_once './config/config.php';
require_once './model/action.php';
require_once './model/resource.php';

header('Content-Type: application/json');

try {
    $pdo = Config::getConnexion();
    
    // Check if we can connect and query
    $testQuery = "SELECT COUNT(*) as total FROM actions WHERE status = 'approved'";
    $testStmt = $pdo->prepare($testQuery);
    $testStmt->execute();
    $totalActions = $testStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Test with France
    $country = $_GET['country'] ?? 'France';
    
    // Check if France exists in the database
    $countryCheck = "SELECT 
        (SELECT COUNT(*) FROM actions WHERE country = :country AND status = 'approved') as action_count,
        (SELECT COUNT(*) FROM resources WHERE country = :country AND status = 'approved') as resource_count";
    
    $countryStmt = $pdo->prepare($countryCheck);
    $countryStmt->bindParam(':country', $country, PDO::PARAM_STR);
    $countryStmt->execute();
    $result = $countryStmt->fetch(PDO::FETCH_ASSOC);
    
    $action = new Action();
    $actions = $action->getByCountry($country);
    
    $resource = new Resource();
    $resources = $resource->getByCountry($country);
    
    echo json_encode([
        'success' => true,
        'debug_info' => [
            'total_actions_in_db' => $totalActions,
            'actions_in_country' => $result['action_count'],
            'resources_in_country' => $result['resource_count'],
            'country_param' => $country,
            'actions_count' => count($actions),
            'resources_count' => count($resources)
        ],
        'actions' => $actions,
        'resources' => $resources,
        'raw_query_results' => [
            'actions' => $result['action_count'],
            'resources' => $result['resource_count']
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>