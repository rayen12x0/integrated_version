<?php
// Simple test to verify endpoint structure
header("Content-Type: application/json");

try {
    // Test if we can include the config file
    require_once '../config/config.php';
    
    // Test if database connection works
    $pdo = Config::getConnexion();
    
    echo json_encode([
        "success" => true,
        "message" => "Endpoint test successful",
        "database_connected" => $pdo !== null,
        "timestamp" => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage(),
        "file" => __FILE__
    ]);
}
?>