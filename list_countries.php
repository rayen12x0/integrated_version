<?php
// Script to list all countries in the database
require_once './config/config.php';

header('Content-Type: application/json');

try {
    $pdo = Config::getConnexion();
    
    // Get all distinct countries from actions
    $actionsCountries = "SELECT DISTINCT country, COUNT(*) as count FROM actions WHERE country IS NOT NULL AND country != '' GROUP BY country ORDER BY count DESC";
    $actionsStmt = $pdo->prepare($actionsCountries);
    $actionsStmt->execute();
    $actionCountries = $actionsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all distinct countries from resources
    $resourcesCountries = "SELECT DISTINCT country, COUNT(*) as count FROM resources WHERE country IS NOT NULL AND country != '' GROUP BY country ORDER BY count DESC";
    $resourcesStmt = $pdo->prepare($resourcesCountries);
    $resourcesStmt->execute();
    $resourceCountries = $resourcesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all unique countries
    $allCountries = [];
    foreach ($actionCountries as $country) {
        if (!empty($country['country'])) {
            $allCountries[$country['country']] = $allCountries[$country['country']] ?? 0;
            $allCountries[$country['country']] += $country['count'];
        }
    }
    
    foreach ($resourceCountries as $country) {
        if (!empty($country['country'])) {
            $allCountries[$country['country']] = $allCountries[$country['country']] ?? 0;
            $allCountries[$country['country']] += $country['count'];
        }
    }
    
    // Sort by count (descending)
    arsort($allCountries);
    
    echo json_encode([
        'success' => true,
        'action_countries' => $actionCountries,
        'resource_countries' => $resourceCountries,
        'all_countries' => $allCountries,
        'unique_countries' => array_keys($allCountries),
        'total_unique_countries' => count($allCountries)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>