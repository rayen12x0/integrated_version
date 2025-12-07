<?php
// Test script to check what database ports are available
$ports_to_test = [3306, 3307, 3308];

foreach ($ports_to_test as $port) {
    echo "Testing connection on port $port...\n";
    
    $servername = "127.0.0.1";
    $username = "root";
    $password = "";
    $dbname = "integrated_version";

    try {
        $pdo = new PDO(
            "mysql:host=$servername;port=$port;dbname=$dbname;charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        // Test connection
        $pdo->query('SELECT 1');
        echo "SUCCESS: Connected to database on port $port\n";
        
        // Check if the tables already exist
        $tables = ['flagged_words', 'content_violations', 'ban_log'];
        foreach ($tables as $table) {
            try {
                $result = $pdo->query("SELECT 1 FROM $table LIMIT 1");
                echo "INFO: Table '$table' exists\n";
            } catch (PDOException $e) {
                echo "INFO: Table '$table' does not exist\n";
            }
        }
        
        break; // If successful, exit the loop
        
    } catch (PDOException $e) {
        echo "FAILED: Could not connect to database on port $port - " . $e->getMessage() . "\n";
    }
}