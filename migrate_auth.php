<?php
// Migration script to add password_resets table to the database
// This should be run once to ensure the table exists

require_once __DIR__ . '/config/config.php';

try {
    $pdo = Config::getConnexion();
    
    // Check if the password_resets table exists
    $checkTable = $pdo->prepare("SHOW TABLES LIKE 'password_resets'");
    $checkTable->execute();
    
    if ($checkTable->rowCount() > 0) {
        echo "Password resets table already exists. No migration needed.\n";
    } else {
        // Create the password_resets table
        $sql = "CREATE TABLE password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_token (token),
            INDEX idx_email (email),
            INDEX idx_expires (expires_at)
        )";
        
        $pdo->exec($sql);
        echo "Password resets table created successfully!\n";
    }
    
    // Also ensure users table has password_reset_token column (for alternative approach)
    $checkColumn = $pdo->prepare("DESCRIBE users");
    $checkColumn->execute();
    $columns = $checkColumn->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('password_reset_token', $columns)) {
        $addColumn = $pdo->prepare("ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(255) NULL AFTER password_hash");
        $addColumn->execute();
        echo "Password reset token column added to users table.\n";
    } else {
        echo "Password reset token column already exists in users table.\n";
    }
    
    if (!in_array('password_reset_expires', $columns)) {
        $addColumn = $pdo->prepare("ALTER TABLE users ADD COLUMN password_reset_expires DATETIME NULL AFTER password_reset_token");
        $addColumn->execute();
        echo "Password reset expires column added to users table.\n";
    } else {
        echo "Password reset expires column already exists in users table.\n";
    }
    
    echo "Migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
}
?>