<?php
// Script to test a specific country by multiple possible names
require_once './config/config.php';

header('Content-Type: application/json');

try {
    $pdo = Config::getConnexion();
    
    $inputCountry = $_GET['country'] ?? '';
    
    if (empty($inputCountry)) {
        echo json_encode([
            'success' => false,
            'message' => 'Country parameter is required'
        ]);
        exit;
    }
    
    // Test different possible variations of the country name
    $possibleNames = [
        $inputCountry, // Exact match
        trim($inputCountry), // Trimmed
        ucwords(strtolower($inputCountry)), // Proper case
        strtoupper($inputCountry), // Uppercase
        strtolower($inputCountry), // Lowercase
    ];
    
    $results = [];
    
    foreach ($possibleNames as $name) {
        if (empty($name)) continue;
        
        $stmt = $pdo->prepare("SELECT 
            (SELECT COUNT(*) FROM actions WHERE country = :country AND status = 'approved') as action_count,
            (SELECT COUNT(*) FROM resources WHERE country = :country AND status = 'approved') as resource_count
        ");
        $stmt->bindParam(':country', $name);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $results[] = [
            'tested_name' => $name,
            'action_count' => $result['action_count'],
            'resource_count' => $result['resource_count'],
            'total' => $result['action_count'] + $result['resource_count']
        ];
    }
    
    // Find the one with the highest count
    $bestMatch = null;
    $maxCount = -1;
    foreach ($results as $result) {
        if ($result['total'] > $maxCount) {
            $maxCount = $result['total'];
            $bestMatch = $result;
        }
    }
    
    echo json_encode([
        'success' => true,
        'input_country' => $inputCountry,
        'tested_variations' => $results,
        'best_match' => $bestMatch,
        'has_data' => $maxCount > 0
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'input_country' => $_GET['country'] ?? ''
    ]);
}
?>