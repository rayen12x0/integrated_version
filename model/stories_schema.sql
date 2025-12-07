-- Stories table (similar to actions table but with story-specific fields)
CREATE TABLE `stories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `creator_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `excerpt` TEXT, -- Shortened version of content
    `author_name` VARCHAR(255) NOT NULL,
    `author_avatar` VARCHAR(10) DEFAULT 'ST', -- For initials display
    `theme` VARCHAR(100), -- e.g., 'Personal', 'Community', 'Inspirational', etc.
    `language` VARCHAR(50) DEFAULT 'en',
    `privacy` ENUM('public', 'private') DEFAULT 'public',
    `status` ENUM('pending', 'approved', 'rejected', 'published', 'draft', 'archived') NOT NULL DEFAULT 'pending',
    `image_url` VARCHAR(500) DEFAULT 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIFN0b3J5IEltYWdlPC90ZXh0Pjwvc3ZnPg==',
    `views` INT UNSIGNED DEFAULT 0,
    `reactions_count` INT UNSIGNED DEFAULT 0,
    `comments_count` INT UNSIGNED DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`creator_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Reactions table for stories
CREATE TABLE `story_reactions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `story_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED,
    `reaction_type` ENUM('heart', 'support', 'inspiration', 'solidarity') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`story_id`) REFERENCES `stories`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `unique_user_story_reaction` (`story_id`, `user_id`, `reaction_type`),
    INDEX `idx_story_id` (`story_id`),
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB;

-- Comments table for stories - already exists but needs to support stories too
-- Adding story_id to existing comments table
ALTER TABLE `comments` ADD COLUMN `story_id` INT UNSIGNED NULL AFTER `resource_id`;
ALTER TABLE `comments` ADD FOREIGN KEY (`story_id`) REFERENCES `stories`(`id`) ON DELETE CASCADE;

-- Update the comments table to allow either action_id, resource_id, or story_id (but only one)
ALTER TABLE `comments` ADD CONSTRAINT `chk_comment_item_type` 
CHECK (
    (action_id IS NOT NULL AND resource_id IS NULL AND story_id IS NULL) OR
    (action_id IS NULL AND resource_id IS NOT NULL AND story_id IS NULL) OR
    (action_id IS NULL AND resource_id IS NULL AND story_id IS NOT NULL)
);

-- Indexes for better performance
CREATE INDEX idx_stories_creator_id ON stories(creator_id);
CREATE INDEX idx_stories_status ON stories(status);
CREATE INDEX idx_stories_theme ON stories(theme);
CREATE INDEX idx_stories_language ON stories(language);
CREATE INDEX idx_stories_privacy ON stories(privacy);