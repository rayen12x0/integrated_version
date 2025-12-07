<?php
require_once 'D:/XAMPP/htdocs/integrated_version/my_work_v2/config/config.php';

try {
    $pdo = Config::getConnexion();

    // First, let's check if the foreign key constraint already exists
    $stmt = $pdo->query("
        SELECT CONSTRAINT_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = 'connect_for_peace'
        AND TABLE_NAME = 'comments'
        AND REFERENCED_TABLE_NAME = 'stories'
    ");

    $result = $stmt->fetchAll();
    if (count($result) > 0) {
        echo "Foreign key constraint already exists.\n";
    } else {
        // Add the foreign key constraint
        $pdo->exec("ALTER TABLE comments ADD FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE;");
        echo "Foreign key constraint added successfully.\n";
    }

} catch (Exception $e) {
    echo "Could not add foreign key: " . $e->getMessage() . "\n";
}
?>