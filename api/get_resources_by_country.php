<?php
// API endpoint to get resources by country
require_once '../config/config.php'; // Need this first to ensure Config class is available
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
    $resource = new Resource();

    // Get resources by country
    $resources = $resource->getByCountry($country);

    echo json_encode([
        'success' => true,
        'resources' => $resources,
        'count' => count($resources),
        'country' => $country,
        'debug' => [
            'input_country' => $country,
            'resources_found' => count($resources)
        ]
    ]);
} catch (Exception $e) {
    error_log('Error getting resources by country: ' . $e->getMessage());
    error_log('Country parameter was: ' . $country);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving resources: ' . $e->getMessage(),
        'country' => $country,
        'debug' => [
            'input_country' => $country
        ]
    ]);
}
?>