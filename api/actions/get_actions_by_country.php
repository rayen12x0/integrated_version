<?php
// API endpoint to get actions by country
require_once '../../config/config.php'; // Need this first to ensure Config class is available
require_once '../../model/action.php';
require_once '../../utils/AuthHelper.php';
require_once '../../utils/CountryNameMapper.php';

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
    $action = new Action();

    // Normalize the input country using CountryNameMapper
    $normalizedCountry = CountryNameMapper::normalizeCountryName($country);
    error_log("Normalized country: '$country' to '$normalizedCountry'");

    // Get all country name variations for fuzzy matching
    $countryVariations = CountryNameMapper::getMappingVariations($country);
    $countryVariations = array_unique($countryVariations);  // Remove duplicates

    error_log("Searching for country variations: " . implode(', ', $countryVariations));

    // Get actions by country variations
    $actions = $action->getByCountry($countryVariations);

    echo json_encode([
        'success' => true,
        'actions' => $actions,
        'count' => count($actions),
        'country' => $normalizedCountry,
        'searched_variations' => $countryVariations,
        'debug' => [
            'input_country' => $country,
            'normalized_country' => $normalizedCountry,
            'variations_used' => $countryVariations,
            'actions_found' => count($actions)
        ]
    ]);
} catch (Exception $e) {
    error_log('Error getting actions by country: ' . $e->getMessage());
    error_log('Country parameter was: ' . $country);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving actions: ' . $e->getMessage(),
        'country' => $country,
        'debug' => [
            'input_country' => $country,
            'error' => $e->getMessage()
        ]
    ]);
}
?>
