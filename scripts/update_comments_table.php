<?php
// scripts/update_comments_table.php
// Updates the comments table to include story_id column if it doesn't exist

require_once __DIR__ . '/../config/config.php';

try {
    $pdo = Config::getConnexion();
    
    // Check if story_id column exists in comments table
    $checkColumnSql = "SHOW COLUMNS FROM comments LIKE 'story_id'";
    $result = $pdo->query($checkColumnSql);
    
    if ($result->rowCount() == 0) {
        // Add story_id column to comments table
        $addColumnSql = "ALTER TABLE comments ADD COLUMN story_id INT UNSIGNED NULL AFTER resource_id";
        $pdo->exec($addColumnSql);
        
        echo "Added story_id column to comments table successfully.\n";
        
        // Add foreign key constraint
        try {
            $addForeignKeySql = "ALTER TABLE comments ADD FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE";
            $pdo->exec($addForeignKeySql);
            echo "Added foreign key constraint for story_id successfully.\n";
        } catch (Exception $e) {
            echo "Could not add foreign key constraint: " . $e->getMessage() . "\n";
            echo "This may be because the constraint already exists or there are data inconsistencies.\n";
        }
    } else {
        echo "story_id column already exists in comments table.\n";
    }
    
    // Also verify that the check constraint is updated to accommodate story_id
    // First drop the existing constraint if it exists
    try {
        $pdo->exec("ALTER TABLE comments DROP CONSTRAINT chk_comment_item_type");
    } catch (Exception $e) {
        // Constraint may not exist, which is fine
        echo "Check constraint may not exist, continuing...\n";
    }
    
    // Add the updated constraint that includes story_id
    try {
        $pdo->exec("
            ALTER TABLE comments ADD CONSTRAINT chk_comment_item_type 
            CHECK (
                (action_id IS NOT NULL AND resource_id IS NULL AND story_id IS NULL) OR
                (action_id IS NULL AND resource_id IS NOT NULL AND story_id IS NULL) OR
                (action_id IS NULL AND resource_id IS NULL AND story_id IS NOT NULL)
            )
        ");
        echo "Updated check constraint to include story_id.\n";
    } catch (Exception $e) {
        echo "Could not add updated check constraint: " . $e->getMessage() . "\n";
    }
    
    echo "Database schema update completed.\n";
    
} catch (Exception $e) {
    echo "Error updating database schema: " . $e->getMessage() . "\n";
}
?>