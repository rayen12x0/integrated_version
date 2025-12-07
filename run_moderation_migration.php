<?php
// Script to run the moderation tables migration
require_once __DIR__ . '/config/config.php';

echo "Starting moderation tables migration...\n";

try {
    // Get database connection
    $pdo = Config::getConnexion();

    // Read the SQL file
    $sql = file_get_contents(__DIR__ . '/model/moderation_tables.sql');

    if ($sql === false) {
        throw new Exception("Could not read the SQL migration file");
    }

    // Execute the SQL
    $statements = explode(';', $sql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }

    echo "Migration completed successfully!\n";
    echo "Created tables: flagged_words, content_violations, ban_log\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}