<?php
// Simple test for France country query
require_once './config/config.php';
require_once './model/action.php';
require_once './model/resource.php';

header('Content-Type: text/html');

try {
    $pdo = Config::getConnexion();
    
    // Check what countries are in the database
    echo "<h2>Available Countries in DB:</h2>";
    
    $stmt = $pdo->query("SELECT DISTINCT country FROM actions WHERE country IS NOT NULL AND country != ''");
    $actionsCountries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>From Actions:</h3><ul>";
    foreach ($actionsCountries as $row) {
        echo "<li>'{$row['country']}'</li>";
    }
    echo "</ul>";
    
    $stmt = $pdo->query("SELECT DISTINCT country FROM resources WHERE country IS NOT NULL AND country != ''");
    $resourcesCountries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>From Resources:</h3><ul>";
    foreach ($resourcesCountries as $row) {
        echo "<li>'{$row['country']}'</li>";
    }
    echo "</ul>";
    
    // Test France query directly
    echo "<h2>Testing France queries:</h2>";
    
    // Exact match
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM actions WHERE country = :country AND status = 'approved'");
    $stmt->bindValue(':country', 'France');
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Exact match 'France' in actions: {$result['count']}<br>";
    
    // Case-insensitive match
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM actions WHERE LOWER(country) = LOWER(:country) AND status = 'approved'");
    $stmt->bindValue(':country', 'France');
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Case-insensitive 'France' in actions: {$result['count']}<br>";
    
    // Test with Action model (which uses our enhanced getByCountry)
    echo "<h2>Testing with Action Model:</h2>";
    $action = new Action();
    $actions = $action->getByCountry('France');
    echo "Actions for 'France': " . count($actions) . "<br>";
    
    $resource = new Resource();
    $resources = $resource->getByCountry('France');
    echo "Resources for 'France': " . count($resources) . "<br>";
    
    // Test with lowercase
    echo "<h2>Testing with lowercase 'france':</h2>";
    $actionsLC = $action->getByCountry('france');
    echo "Actions for 'france': " . count($actionsLC) . "<br>";
    
    $resourcesLC = $resource->getByCountry('france');
    echo "Resources for 'france': " . count($resourcesLC) . "<br>";
    
    echo "<h2>Test completed successfully!</h2>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>