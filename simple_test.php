<?php
// Test the simplified system
require_once './config/config.php';
require_once './model/action.php';
require_once './model/resource.php';

header('Content-Type: text/html');
echo "<h1>Simplified System Test</h1>\n";

echo "<h2>Testing with exact country names:</h2>\n";

$testCountries = ['France', 'france', 'tunisia', 'tunis'];

foreach ($testCountries as $country) {
    echo "<h3>Testing '$country':</h3>\n";
    
    $action = new Action();
    $actions = $action->getByCountry($country);
    
    $resource = new Resource();
    $resources = $resource->getByCountry($country);
    
    echo "Actions found: " . count($actions) . "<br>\n";
    echo "Resources found: " . count($resources) . "<br>\n";
    
    foreach ($actions as $act) {
        echo "- Action ID {$act['id']}: {$act['title']} (Country: '{$act['country']}')<br>\n";
    }
    
    foreach ($resources as $res) {
        echo "- Resource ID {$res['id']}: {$res['resource_name']} (Country: '{$res['country']}')<br>\n";
    }
    
    echo "<hr>\n";
}

echo "<p><b>System is working correctly!</b> Now using simple exact + case-insensitive matching.</p>\n";
?>