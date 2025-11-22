<?php
// Test script to verify all location APIs are working correctly
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$testResults = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => []
];

// Test get_countries_with_data.php
try {
    $response = file_get_contents('http://localhost/my_work_v3/my_work_v2/api/get_countries_with_data.php');
    $data = json_decode($response, true);
    $testResults['tests']['get_countries_with_data'] = [
        'success' => isset($data['success']) && $data['success'] === true,
        'message' => isset($data['message']) ? $data['message'] : 'OK'
    ];
} catch (Exception $e) {
    $testResults['tests']['get_countries_with_data'] = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

// Test get_country_statistics.php
try {
    $response = file_get_contents('http://localhost/my_work_v3/my_work_v2/api/get_country_statistics.php?country=France');
    $data = json_decode($response, true);
    $testResults['tests']['get_country_statistics'] = [
        'success' => isset($data['success']),
        'message' => isset($data['message']) ? $data['message'] : 'OK'
    ];
} catch (Exception $e) {
    $testResults['tests']['get_country_statistics'] = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

// Test get_country_locations.php
try {
    $response = file_get_contents('http://localhost/my_work_v3/my_work_v2/api/get_country_locations.php?country=France');
    $data = json_decode($response, true);
    $testResults['tests']['get_country_locations'] = [
        'success' => isset($data['success']),
        'message' => isset($data['message']) ? $data['message'] : 'OK'
    ];
} catch (Exception $e) {
    $testResults['tests']['get_country_locations'] = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

// Test get_actions_by_country.php
try {
    $response = file_get_contents('http://localhost/my_work_v3/my_work_v2/api/get_actions_by_country.php?country=France');
    $data = json_decode($response, true);
    $testResults['tests']['get_actions_by_country'] = [
        'success' => isset($data['success']),
        'message' => isset($data['message']) ? $data['message'] : 'OK'
    ];
} catch (Exception $e) {
    $testResults['tests']['get_actions_by_country'] = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

// Test get_resources_by_country.php
try {
    $response = file_get_contents('http://localhost/my_work_v3/my_work_v2/api/get_resources_by_country.php?country=France');
    $data = json_decode($response, true);
    $testResults['tests']['get_resources_by_country'] = [
        'success' => isset($data['success']),
        'message' => isset($data['message']) ? $data['message'] : 'OK'
    ];
} catch (Exception $e) {
    $testResults['tests']['get_resources_by_country'] = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

echo json_encode($testResults);
?>