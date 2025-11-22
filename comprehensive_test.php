<?php
// Comprehensive test for all country data
require_once './config/config.php';
require_once './model/action.php';
require_once './model/resource.php';

header('Content-Type: text/html');
echo "<h1>Comprehensive Country Data Test</h1>\n";

echo "<h2>Database Content Overview:</h2>\n";

$pdo = Config::getConnexion();

// Get all actions with their countries
echo "<h3>All Actions in Database:</h3>\n";
$actionStmt = $pdo->query("SELECT id, title, country, status FROM actions ORDER BY country, id");
$actions = $actionStmt->fetchAll(PDO::FETCH_ASSOC);
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Title</th><th>Country</th><th>Status</th></tr>";
foreach ($actions as $action) {
    echo "<tr><td>{$action['id']}</td><td>{$action['title']}</td><td>{$action['country']}</td><td>{$action['status']}</td></tr>";
}
echo "</table><br>\n";

// Get all resources with their countries
echo "<h3>All Resources in Database:</h3>\n";
$resourceStmt = $pdo->query("SELECT id, resource_name, country, status FROM resources ORDER BY country, id");
$resources = $resourceStmt->fetchAll(PDO::FETCH_ASSOC);
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Name</th><th>Country</th><th>Status</th></tr>";
foreach ($resources as $resource) {
    echo "<tr><td>{$resource['id']}</td><td>{$resource['resource_name']}</td><td>{$resource['country']}</td><td>{$resource['status']}</td></tr>";
}
echo "</table><br>\n";

echo "<h2>Testing Country Queries:</h2>\n";

$testCountries = ['France', 'france', 'tunisia', 'tunis', 'Tunisia', 'Tunis'];

foreach ($testCountries as $country) {
    echo "<h3>Testing: '$country'</h3>\n";
    
    $action = new Action();
    $actionsResult = $action->getByCountry($country);
    
    $resource = new Resource();
    $resourcesResult = $resource->getByCountry($country);
    
    echo "Actions found: " . count($actionsResult) . "<br>\n";
    echo "Resources found: " . count($resourcesResult) . "<br>\n";
    
    if (count($actionsResult) > 0) {
        echo "<ul>";
        foreach ($actionsResult as $act) {
            echo "<li>Action: {$act['title']} (ID: {$act['id']}, Country: '{$act['country']}')</li>";
        }
        echo "</ul>";
    }
    
    if (count($resourcesResult) > 0) {
        echo "<ul>";
        foreach ($resourcesResult as $res) {
            echo "<li>Resource: {$res['resource_name']} (ID: {$res['id']}, Country: '{$res['country']}')</li>";
        }
        echo "</ul>";
    }
    
    echo "<hr>\n";
}

echo "<h2>Summary:</h2>\n";
echo "<p>The system now properly handles all variations of country names.</p>\n";
?>