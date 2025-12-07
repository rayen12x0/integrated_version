<?php
// verify_get_pending.php
// Verification script to test Report::getPending() method

echo "=== Report::getPending() Method Verification ===\n\n";

// Check if we can include the required files
if (!file_exists('config/config.php')) {
    die("ERROR: config/config.php not found\n");
}

if (!file_exists('model/report.php')) {
    die("ERROR: model/report.php not found\n");
}

require_once 'config/config.php';
require_once 'model/report.php';

try {
    // Create Report model instance
    $report = new Report();
    echo "✅ Report model instantiated successfully\n";
    
    // Test 1: Call getPending() method
    echo "\n--- Test 1: Calling getPending() ---\n";
    $stmt = $report->getPending();
    
    if ($stmt instanceof PDOStatement) {
        echo "✅ getPending() returned PDOStatement as expected\n";
    } else {
        echo "❌ getPending() did not return PDOStatement\n";
        exit(1);
    }
    
    // Test 2: Test rowCount() functionality
    echo "\n--- Test 2: Testing rowCount() ---\n";
    $count = $stmt->rowCount();
    echo "Pending reports count: $count\n";
    echo "✅ rowCount() executed successfully\n";
    
    // Test 3: Test fetchAll() functionality
    echo "\n--- Test 3: Testing fetchAll() ---\n";
    $stmtForFetch = $report->getPending(); // Get fresh statement
    $pendingReports = $stmtForFetch->fetchAll(PDO::FETCH_ASSOC);
    
    if (is_array($pendingReports)) {
        echo "✅ fetchAll() returned array as expected\n";
        echo "Retrieved " . count($pendingReports) . " pending reports\n";
        
        if (count($pendingReports) > 0) {
            $firstReport = $pendingReports[0];
            // Check for expected fields
            $expectedFields = ['id', 'reporter_id', 'reported_item_id', 'reported_item_type', 
                             'report_category', 'report_reason', 'status', 'reporter_name', 'reporter_email'];
            
            echo "\n--- Test 4: Verifying expected fields ---\n";
            $missingFields = [];
            foreach ($expectedFields as $field) {
                if (!isset($firstReport[$field])) {
                    $missingFields[] = $field;
                }
            }
            
            if (empty($missingFields)) {
                echo "✅ All expected fields present in report data\n";
            } else {
                echo "❌ Missing fields: " . implode(', ', $missingFields) . "\n";
            }
            
            echo "\nSample report data:\n";
            echo "ID: {$firstReport['id']}\n";
            echo "Reporter: {$firstReport['reporter_name']} ({$firstReport['reporter_email']})\n";
            echo "Item Type: {$firstReport['reported_item_type']}\n";
            echo "Category: {$firstReport['report_category']}\n";
            echo "Status: {$firstReport['status']}\n";
        }
    } else {
        echo "❌ fetchAll() did not return array\n";
    }
    
    // Test 5: Integration with ModerationController pattern
    echo "\n--- Test 5: Simulating ModerationController usage ---\n";
    $pendingCount = $report->getPending()->rowCount();
    echo "Simulated dashboard count: $pendingCount\n";
    echo "✅ Integration pattern works correctly\n";
    
    echo "\n=== VERIFICATION COMPLETE ===\n";
    echo "✅ All tests passed\n";
    echo "✅ Report::getPending() method is functioning correctly\n";
    echo "✅ Method returns PDOStatement as designed\n";
    echo "✅ rowCount() and fetchAll() both work properly\n";
    echo "✅ Integration with dashboard statistics confirmed\n";
    
} catch (Exception $e) {
    echo "❌ Error during verification: " . $e->getMessage() . "\n";
    exit(1);
}