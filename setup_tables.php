<?php
// setup_tables.php
// Script to create missing tables for moderation features

require_once __DIR__ . '/config/config.php';

try {
    $pdo = Config::getConnexion();
    echo "Connected to database.\n";

    // 1. Create flagged_words table
    $sql = "CREATE TABLE IF NOT EXISTS `flagged_words` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `word` VARCHAR(100) NOT NULL UNIQUE,
        `category` ENUM('hate_speech', 'profanity', 'harassment', 'spam', 'other') NOT NULL,
        `severity` ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
        `auto_action` ENUM('flag', 'reject') NOT NULL DEFAULT 'flag',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;";
    
    $pdo->exec($sql);
    echo "Table 'flagged_words' checked/created.\n";

    // Insert default flagged words if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM flagged_words");
    if ($stmt->fetchColumn() == 0) {
        $words = [
            ['badword1', 'profanity', 'low', 'flag'],
            ['hateword1', 'hate_speech', 'critical', 'reject'],
            ['spamlink', 'spam', 'high', 'reject'],
            ['harass', 'harassment', 'medium', 'flag']
        ];
        
        $insert = $pdo->prepare("INSERT INTO flagged_words (word, category, severity, auto_action) VALUES (?, ?, ?, ?)");
        foreach ($words as $w) {
            $insert->execute($w);
        }
        echo "Inserted default flagged words.\n";
    }

    // 2. Create content_violations table
    $sql = "CREATE TABLE IF NOT EXISTS `content_violations` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `content_type` ENUM('story', 'comment', 'action', 'resource') NOT NULL,
        `content_id` INT UNSIGNED NOT NULL,
        `user_id` INT UNSIGNED NOT NULL,
        `flagged_word` VARCHAR(100) NOT NULL,
        `word_category` VARCHAR(50) NOT NULL,
        `severity` VARCHAR(20) NOT NULL,
        `status` ENUM('pending', 'reviewed', 'dismissed') NOT NULL DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB;";
    
    $pdo->exec($sql);
    echo "Table 'content_violations' checked/created.\n";

    // 3. Create ban_log table (optional but good practice)
    $sql = "CREATE TABLE IF NOT EXISTS `ban_logs` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED NOT NULL,
        `banned_by` INT UNSIGNED NOT NULL,
        `reason` TEXT,
        `banned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`banned_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB;";
    
    $pdo->exec($sql);
    echo "Table 'ban_logs' checked/created.\n";

    echo "Setup completed successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
