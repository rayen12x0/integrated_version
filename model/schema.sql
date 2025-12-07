-- Database Schema for Connect for Peace Application
-- Creates all necessary tables for the application

-- Create the database
CREATE DATABASE IF NOT EXISTS `integrated_version` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `integrated_version`;

-- Users table
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `avatar_url` VARCHAR(500),
    `badge` VARCHAR(100) DEFAULT 'Community Member',
    `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    `country` VARCHAR(100),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_active` TINYINT(1) DEFAULT TRUE
) ENGINE=InnoDB;

-- Actions table
CREATE TABLE `actions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `creator_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `category` VARCHAR(50), -- e.g., Environment, Education, etc.
    `theme` VARCHAR(100), -- e.g., Cleanup, Workshop, etc.
    `location` VARCHAR(255),
    `country` VARCHAR(100),
    `latitude` DECIMAL(10, 8),
    `longitude` DECIMAL(11, 8),
    `start_time` DATETIME,
    `end_time` DATETIME,
    `status` ENUM('pending', 'approved', 'rejected', 'active', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    `image_url` VARCHAR(500) DEFAULT 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEFjdGlvbiBJbWFnZTwvdGV4dD48L3N2Zz4=',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`creator_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Resources table
CREATE TABLE `resources` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `publisher_id` INT UNSIGNED NOT NULL,
    `resource_name` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `type` ENUM('offer', 'request', 'knowledge') NOT NULL,
    `category` VARCHAR(50), -- e.g., Books, Furniture, etc.
    `location` VARCHAR(255),
    `country` VARCHAR(100),
    `latitude` DECIMAL(10, 8),
    `longitude` DECIMAL(11, 8),
    `status` ENUM('pending', 'approved', 'rejected', 'available', 'claimed') NOT NULL DEFAULT 'pending',
    `image_url` VARCHAR(500) DEFAULT 'https://via.placeholder.com/400x200?text=Resource+Image',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`publisher_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

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

-- Action Participants table
CREATE TABLE `action_participants` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `action_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`action_id`) REFERENCES `actions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_participation` (`action_id`, `user_id`)
) ENGINE=InnoDB;

-- Comments table (supports actions, resources, and stories)
CREATE TABLE `comments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `action_id` INT UNSIGNED NULL,
    `resource_id` INT UNSIGNED NULL,
    `story_id` INT UNSIGNED NULL,  -- Added for stories support
    `content` TEXT NOT NULL,
    `status` ENUM('active', 'flagged', 'deleted') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`action_id`) REFERENCES `actions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`resource_id`) REFERENCES `resources`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`story_id`) REFERENCES `stories`(`id`) ON DELETE CASCADE,
    CHECK (
        (action_id IS NOT NULL AND resource_id IS NULL AND story_id IS NULL) OR
        (action_id IS NULL AND resource_id IS NOT NULL AND story_id IS NULL) OR
        (action_id IS NULL AND resource_id IS NULL AND story_id IS NOT NULL)
    )
) ENGINE=InnoDB;

-- Story Reactions table
CREATE TABLE `story_reactions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `story_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NULL, -- Can be NULL for anonymous reactions
    `reaction_type` ENUM('heart', 'support', 'inspiration', 'solidarity') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`story_id`) REFERENCES `stories`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `unique_user_story_reaction` (`story_id`, `user_id`, `reaction_type`),
    INDEX `idx_story_id` (`story_id`),
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB;

-- Notifications table for admin approvals
CREATE TABLE `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL, -- Recipient of notification
    `type` ENUM('action_created', 'resource_created', 'story_created', 'action_updated', 'resource_updated', 'story_updated', 'action_deleted', 'resource_deleted', 'story_deleted', 'action_approved', 'resource_approved', 'story_approved', 'action_rejected', 'resource_rejected', 'story_rejected', 'reminder', 'report_created') NOT NULL,
    `message` TEXT NOT NULL,
    `related_id` INT UNSIGNED, -- Can reference action_id, resource_id, or story_id
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Reports table
CREATE TABLE `reports` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `reporter_id` INT UNSIGNED NOT NULL,
    `reported_item_id` INT UNSIGNED NOT NULL,
    `reported_item_type` ENUM('action', 'resource', 'story', 'comment', 'user') NOT NULL, -- Updated to include 'story'
    `report_category` ENUM('scam', 'spam', 'inappropriate', 'fake', 'hate_speech', 'harassment', 'other') NOT NULL,
    `report_reason` TEXT NOT NULL,
    `status` ENUM('pending', 'reviewed', 'resolved', 'dismissed') NOT NULL DEFAULT 'pending',
    `admin_notes` TEXT,
    `reviewed_by` INT UNSIGNED,
    `reviewed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`reporter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Reminders table for user reminders
CREATE TABLE `reminders` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `item_id` INT UNSIGNED NOT NULL,
    `item_type` ENUM('action', 'resource', 'story') NOT NULL, -- Updated to include 'story'
    `reminder_time` DATETIME NOT NULL,
    `reminder_type` ENUM('email', 'in_app', 'both') DEFAULT 'both',
    `sent` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_item` (`item_id`, `item_type`),
    INDEX `idx_reminder_time` (`reminder_time`),
    INDEX `idx_sent` (`sent`)
) ENGINE=InnoDB;

-- Create indexes for better performance
CREATE INDEX idx_actions_creator_id ON actions(creator_id);
CREATE INDEX idx_actions_status ON actions(status);
CREATE INDEX idx_actions_country ON actions(country);
CREATE INDEX idx_resources_publisher_id ON resources(publisher_id);
CREATE INDEX idx_resources_status ON resources(status);
CREATE INDEX idx_resources_country ON resources(country);
CREATE INDEX idx_stories_creator_id ON stories(creator_id);  -- Added for stories
CREATE INDEX idx_stories_status ON stories(status);         -- Added for stories
CREATE INDEX idx_stories_theme ON stories(theme);           -- Added for stories
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_reports_reporter_id ON reports(reporter_id);
CREATE INDEX idx_reports_status ON reports(status);
CREATE INDEX idx_comments_user_id ON comments(user_id);
CREATE INDEX idx_comments_action_id ON comments(action_id);      -- Added for comments on stories
CREATE INDEX idx_comments_resource_id ON comments(resource_id);  -- Added for comments on stories
CREATE INDEX idx_comments_story_id ON comments(story_id);        -- Added for comments on stories

-- Insert default admin user
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `avatar_url`, `badge`)
VALUES ('Admin User', 'admin@connectforpeace.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'https://api.dicebear.com/7.x/avataaars/svg?seed=admin', 'Administrator');

-- Insert default regular user
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `avatar_url`, `badge`)
VALUES ('Regular User', 'rayen12x@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'https://api.dicebear.com/7.x/avataaars/svg?seed=regular', 'Community Member');

-- Insert sample actions
INSERT INTO `actions` (`creator_id`, `title`, `description`, `category`, `theme`, `location`, `country`, `start_time`, `end_time`, `status`, `image_url`) VALUES
(1, 'Community Garden Cleanup', 'Join us for a day of cleaning and maintaining our local community garden. Bring gloves and a positive attitude!', 'Environment', 'Cleanup', 'Central Park, Paris', 'France', '2025-01-15 10:00:00', '2025-01-15 14:00:00', 'approved', 'https://images.unsplash.com/photo-1542838132-92c53300491e?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'),
(1, 'Educational Workshop', 'Learn about sustainable living practices and how to create eco-friendly habits at home.', 'Education', 'Workshop', 'Public Library, Paris', 'France', '2025-01-20 14:00:00', '2025-01-20 16:00:00', 'approved', 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'),
(2, 'Social Support Meetup', 'Monthly meetup for local social support initiatives and sharing best practices.', 'Social Help', 'Mentoring', 'Community Center, Lyon', 'France', '2025-01-25 16:00:00', '2025-01-25 18:00:00', 'approved', 'https://images.unsplash.com/photo-1558591718-50d24a5b2c33?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80');

-- Insert sample resources
INSERT INTO `resources` (`publisher_id`, `resource_name`, `description`, `type`, `category`, `location`, `country`, `latitude`, `longitude`, `status`, `image_url`) VALUES
(1, 'School Books Collection', 'Complete set of school books for children aged 6-12, in excellent condition.', 'offer', 'Books', 'Paris Libraries Network', 'France', 48.8566, 2.3522, 'approved', 'https://images.unsplash.com/photo-1535905557558-afc487d8eccd?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'),
(1, 'Furniture Donation', 'Unused furniture including sofa, dining table and chairs available for donation.', 'offer', 'Furniture', 'Charitable Organizations, France', 'France', 48.8566, 2.3522, 'approved', 'https://images.unsplash.com/photo-1513694203232-719a20d9c6c7?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'),
(2, 'Volunteer Teaching Help', 'Looking for volunteers to teach basic computer skills to seniors.', 'request', 'Knowledge', 'Senior Centers, Lyon', 'France', 45.7640, 4.8357, 'approved', 'https://images.unsplash.com/photo-1522202178754-37bc9c1d7ed2?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80');

-- Insert sample stories
INSERT INTO `stories` (`creator_id`, `title`, `content`, `excerpt`, `author_name`, `author_avatar`, `theme`, `language`, `privacy`, `status`, `image_url`) VALUES
(1, 'My Journey to Peace', 'Join me as I share my personal journey towards finding peace and reconciliation in my community...', 'Personal account of finding peace through community involvement...', 'Alice Johnson', 'AJ', 'Personal', 'en', 'public', 'approved', 'https://images.unsplash.com/photo-1507842217343-583bb7270b66?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'),
(2, 'Building Bridges Between Communities', 'A heartfelt story about connecting different communities through small acts of kindness...', 'How small gestures can create meaningful connections between communities...', 'Bob Martin', 'BM', 'Community', 'en', 'public', 'approved', 'https://images.unsplash.com/photo-1529338296759-201ce48d7e9b?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'),
(1, 'Art as Healing', 'Exploring how creative expression can serve as a healing force in times of conflict...', 'Using art as a therapeutic tool for recovery and peace building...', 'Alice Johnson', 'AJ', 'Art & Culture', 'en', 'public', 'published', 'https://images.unsplash.com/photo-1579546929662-711aa81148cf?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80');

-- Insert sample action participants
INSERT INTO `action_participants` (`action_id`, `user_id`) VALUES
(1, 2), (1, 3), -- 2 people joining the garden cleanup besides creator
(2, 3); -- 1 person joining the workshop

-- Insert sample comments
INSERT INTO `comments` (`user_id`, `action_id`, `resource_id`, `story_id`, `content`, `status`) VALUES
(2, 1, NULL, NULL, 'Looking forward to helping out! Should I bring my own work gloves?', 'active'),
(3, 1, NULL, NULL, 'I have extra gardening tools if anyone needs them!', 'active'),
(1, NULL, 1, NULL, 'These books would be perfect for our school library!', 'active'),
(2, NULL, NULL, 1, 'Thank you for sharing such an inspiring story!', 'active'),
(3, NULL, NULL, 2, 'This resonates with my experience too.', 'active');

-- Insert sample story reactions
INSERT INTO `story_reactions` (`story_id`, `user_id`, `reaction_type`) VALUES
(1, 2, 'heart'),
(1, 3, 'support'),
(2, 1, 'inspiration'),
(2, 3, 'solidarity'),
(3, 2, 'heart');

-- Insert sample notifications
INSERT INTO `notifications` (`user_id`, `type`, `message`, `related_id`) VALUES
(1, 'action_created', 'New action "Community Garden Cleanup" has been submitted for approval by Alice Johnson.', 1),
(1, 'resource_created', 'New resource "School Books Collection" has been submitted for approval by Alice Johnson.', 1),
(1, 'story_created', 'New story "My Journey to Peace" has been submitted for approval by Alice Johnson.', 1), -- Added for stories
(2, 'action_approved', 'Your action "Community Garden Cleanup" has been approved and is now visible to the public.', 1),
(3, 'action_joined', 'Bob Martin has joined your action "Educational Workshop".', 2);

-- Insert sample reports
INSERT INTO `reports` (`reporter_id`, `reported_item_id`, `reported_item_type`, `report_category`, `report_reason`, `status`) VALUES
(2, 1, 'action', 'inappropriate', 'Title seems misleading about actual activity', 'pending'),
(3, 2, 'resource', 'spam', 'Same user posting multiple similar requests', 'pending'),
(2, 1, 'story', 'inappropriate', 'Content may be offensive', 'pending'); -- Added for stories

-- Insert sample reminders
INSERT INTO `reminders` (`user_id`, `item_id`, `item_type`, `reminder_time`, `reminder_type`) VALUES
(2, 1, 'action', '2025-01-14 08:00:00', 'both'),
(3, 2, 'action', '2025-01-20 12:00:00', 'email'),
(1, 1, 'story', '2025-02-01 10:00:00', 'both'); -- Added for stories

-- Table: flagged_words
-- Purpose: Store inappropriate words for auto-moderation
CREATE TABLE `flagged_words` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `word` VARCHAR(100) NOT NULL UNIQUE,
    `category` ENUM('profanity', 'hate_speech', 'spam', 'violence', 'sexual') NOT NULL,
    `severity` ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    `auto_action` ENUM('flag', 'reject') NOT NULL DEFAULT 'flag',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_word` (`word`),
    INDEX `idx_severity` (`severity`)
) ENGINE=InnoDB;

-- Table: content_violations
-- Purpose: Log all content moderation violations
CREATE TABLE `content_violations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `content_type` ENUM('comment', 'story', 'action', 'resource') NOT NULL,
    `content_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `flagged_word` VARCHAR(100) NOT NULL,
    `word_category` VARCHAR(50) NOT NULL,
    `severity` VARCHAR(20) NOT NULL,
    `status` ENUM('pending', 'reviewed', 'dismissed') DEFAULT 'pending',
    `reviewed_by` INT UNSIGNED NULL,
    `reviewed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_content` (`content_type`, `content_id`)
) ENGINE=InnoDB;

-- Table: ban_log
-- Purpose: Track user ban history for audit trail
CREATE TABLE `ban_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `banned_by` INT UNSIGNED NOT NULL,
    `reason` TEXT NOT NULL,
    `action_type` ENUM('ban', 'unban') NOT NULL,
    `banned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `unbanned_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`banned_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_banned_by` (`banned_by`),
    INDEX `idx_action_type` (`action_type`)
) ENGINE=InnoDB;

-- Insert seed data for flagged_words
INSERT INTO `flagged_words` (`word`, `category`, `severity`, `auto_action`) VALUES
('fuck', 'profanity', 'medium', 'flag'),
('fucking', 'profanity', 'medium', 'flag'),
('fucked', 'profanity', 'medium', 'flag'),
('shit', 'profanity', 'low', 'flag'),
('damn', 'profanity', 'low', 'flag'),
('bitch', 'profanity', 'medium', 'flag'),
('bastard', 'profanity', 'high', 'flag'),
('asshole', 'profanity', 'high', 'flag'),
('dick', 'profanity', 'high', 'flag'),
('cunt', 'profanity', 'critical', 'reject'),
('nigger', 'hate_speech', 'critical', 'reject'),
('nigga', 'hate_speech', 'critical', 'reject'),
('faggot', 'hate_speech', 'critical', 'reject'),
('slut', 'sexual', 'high', 'flag'),
('whore', 'sexual', 'high', 'flag'),
('porn', 'sexual', 'medium', 'flag'),
('naked', 'sexual', 'low', 'flag'),
('sex', 'sexual', 'low', 'flag'),
('kill', 'violence', 'high', 'flag'),
('murder', 'violence', 'critical', 'flag'),
('rape', 'violence', 'critical', 'flag'),
('terrorist', 'hate_speech', 'high', 'flag'),
('racist', 'hate_speech', 'high', 'flag'),
('hate', 'hate_speech', 'medium', 'flag'),
('spam', 'spam', 'medium', 'flag'),
('click here', 'spam', 'low', 'flag'),
('free money', 'spam', 'high', 'flag'),
('click now', 'spam', 'medium', 'flag'),
('make money', 'spam', 'medium', 'flag'),
('casino', 'spam', 'medium', 'flag'),
('pills', 'spam', 'medium', 'flag'),
('viagra', 'spam', 'medium', 'flag'),
('loan', 'spam', 'low', 'flag'),
('scam', 'spam', 'high', 'flag'),
('fake', 'spam', 'medium', 'flag'),
('urgent', 'spam', 'low', 'flag'),
('act now', 'spam', 'low', 'flag'),
('limited time', 'spam', 'low', 'flag'),
('winner', 'spam', 'high', 'flag'),
('congratulations', 'spam', 'medium', 'flag'),
('click below', 'spam', 'medium', 'flag'),
('get rich', 'spam', 'high', 'flag'),
('no prescription', 'spam', 'medium', 'flag'),
('pump up', 'sexual', 'medium', 'flag'),
('enlarge', 'sexual', 'medium', 'flag'),
('biggest', 'sexual', 'low', 'flag'),
('penis', 'sexual', 'medium', 'flag'),
('vagina', 'sexual', 'medium', 'flag'),
('breast', 'sexual', 'low', 'flag'),
('nude', 'sexual', 'medium', 'flag'),
('boobs', 'sexual', 'medium', 'flag'),
('ass', 'sexual', 'medium', 'flag'),
('butt', 'sexual', 'low', 'flag'),
('horny', 'sexual', 'high', 'flag'),
('nympho', 'sexual', 'high', 'flag'),
('pedo', 'violence', 'critical', 'reject'),
('pedophile', 'violence', 'critical', 'reject'),
('kill yourself', 'violence', 'critical', 'flag'),
('suicide', 'violence', 'high', 'flag'),
('die', 'violence', 'high', 'flag'),
('blood', 'violence', 'medium', 'flag'),
('gore', 'violence', 'high', 'flag'),
('torture', 'violence', 'high', 'flag'),
('abuse', 'violence', 'high', 'flag'),
('abusive', 'violence', 'high', 'flag'),
('abduct', 'violence', 'high', 'flag'),
('abductor', 'violence', 'high', 'flag'),
('attack', 'violence', 'medium', 'flag'),
('attacker', 'violence', 'medium', 'flag'),
('harm', 'violence', 'medium', 'flag'),
('harmful', 'violence', 'medium', 'flag'),
('hurt', 'violence', 'medium', 'flag'),
('hurting', 'violence', 'medium', 'flag'),
('hit', 'violence', 'low', 'flag'),
('hitting', 'violence', 'low', 'flag'),
('injure', 'violence', 'medium', 'flag'),
('injuring', 'violence', 'medium', 'flag'),
('injury', 'violence', 'medium', 'flag'),
('injured', 'violence', 'medium', 'flag'),
('violence', 'violence', 'high', 'flag'),
('violent', 'violence', 'high', 'flag'),
('aggressive', 'violence', 'medium', 'flag'),
('aggression', 'violence', 'medium', 'flag'),
('aggressor', 'violence', 'medium', 'flag'),
('assault', 'violence', 'high', 'flag'),
('assaulter', 'violence', 'high', 'flag'),
('bully', 'violence', 'medium', 'flag'),
('bullying', 'violence', 'medium', 'flag'),
('bullyer', 'violence', 'medium', 'flag');