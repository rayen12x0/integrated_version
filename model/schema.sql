-- Connect for Peace - Complete Schema with User Authentication
-- This SQL script creates the complete database schema including users table

-- General Setup
DROP DATABASE IF EXISTS `connect_for_peace`;
CREATE DATABASE IF NOT EXISTS `connect_for_peace` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `connect_for_peace`;

-- Users table
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    `avatar_url` VARCHAR(500),
    `badge` VARCHAR(100) DEFAULT 'Community Member',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
    `latitude` DECIMAL(10, 8),
    `longitude` DECIMAL(11, 8),
    `country` VARCHAR(100),
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
    `type` ENUM('offer', 'request') NOT NULL,
    `category` VARCHAR(50), -- e.g., Books, Furniture, etc.
    `location` VARCHAR(255),
    `latitude` DECIMAL(10, 8),
    `longitude` DECIMAL(11, 8),
    `country` VARCHAR(100),
    `status` ENUM('pending', 'approved', 'rejected', 'open', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    `image_url` VARCHAR(500) DEFAULT 'https://via.placeholder.com/400x200?text=Resource+Image',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`publisher_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Join table for users participating in actions
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

-- Comments table for actions and resources
CREATE TABLE `comments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `action_id` INT UNSIGNED, -- Can be NULL if commenting on resource
    `resource_id` INT UNSIGNED, -- Can be NULL if commenting on action
    `user_id` INT UNSIGNED NOT NULL,
    `content` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`action_id`) REFERENCES `actions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`resource_id`) REFERENCES `resources`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Notifications table for admin approvals
CREATE TABLE `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL, -- Recipient of notification
    `type` ENUM('action_created', 'resource_created', 'action_updated', 'resource_updated', 'action_deleted', 'resource_deleted', 'action_approved', 'resource_approved', 'action_rejected', 'resource_rejected', 'reminder', 'report_created') NOT NULL,
    `message` TEXT NOT NULL,
    `related_id` INT UNSIGNED, -- ID of related action/resource
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Reports table for user reports
CREATE TABLE `reports` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `reporter_id` INT UNSIGNED NOT NULL,
    `reported_item_id` INT UNSIGNED NOT NULL,
    `reported_item_type` ENUM('action', 'resource') NOT NULL,
    `report_category` ENUM('scam', 'spam', 'inappropriate', 'fake', 'other') NOT NULL,
    `report_reason` TEXT NOT NULL,
    `status` ENUM('pending', 'reviewed', 'resolved') NOT NULL DEFAULT 'pending',
    `admin_notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`reporter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_reporter_id` (`reporter_id`),
    INDEX `idx_reported_item` (`reported_item_id`, `reported_item_type`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB;

-- Reminders table for user reminders
CREATE TABLE `reminders` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `item_id` INT UNSIGNED NOT NULL,
    `item_type` ENUM('action', 'resource') NOT NULL,
    `reminder_type` ENUM('email', 'in_app', 'both') NOT NULL DEFAULT 'both',
    `reminder_time` DATETIME NOT NULL,
    `sent` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_item` (`item_id`, `item_type`),
    INDEX `idx_reminder_time` (`reminder_time`),
    INDEX `idx_sent` (`sent`)
) ENGINE=InnoDB;

-- Insert default admin user
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `avatar_url`, `badge`)
VALUES ('Admin User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'https://api.placeholder.com/40/40?text=AU', 'Administrator');

-- Insert default regular user
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `avatar_url`, `badge`)
VALUES ('Regular User', 'rayen12x@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'https://api.placeholder.com/40/40?text=R', 'Community Member');

-- Insert sample actions
INSERT INTO `actions` (`creator_id`, `title`, `description`, `category`, `theme`, `location`, `country`, `start_time`, `status`, `image_url`)
VALUES
(1, 'Community Garden Cleanup', 'Join us in cleaning up the community garden.', 'Environment', 'Cleanup', 'Central Park, Paris', 'France', '2025-01-15 10:00:00', 'approved', 'https://via.placeholder.com/400x200?text=Garden+Cleanup'),
(1, 'Educational Workshop', 'Learn about sustainable living practices.', 'Education', 'Workshop', 'Public Library, Paris', 'France', '2025-01-20 14:00:00', 'approved', 'https://via.placeholder.com/400x200?text=Educational+Workshop'),
(2, 'Social Support Meetup', 'Meeting for local social support initiatives.', 'Social Help', 'Community', 'Local Community Center', 'France', '2025-01-25 16:00:00', 'approved', 'https://via.placeholder.com/400x200?text=Support+Meetup');

-- Insert sample resources
INSERT INTO `resources` (`publisher_id`, `resource_name`, `description`, `type`, `category`, `location`, `country`, `status`, `image_url`)
VALUES
(1, 'School Books', 'Set of school books for children.', 'offer', 'Books', '15 Avenue des Champs-Élysées, Paris', 'France', 'approved', 'https://via.placeholder.com/400x200?text=School+Books'),
(1, 'Furniture Donation', 'Old furniture available for donation.', 'offer', 'Furniture', '123 Rue de la Paix, Paris', 'France', 'approved', 'https://via.placeholder.com/400x200?text=Furniture+Donation'),
(2, 'Carpenter Help', 'Looking for someone with carpentry skills.', 'request', 'Skills', 'Nearby Area', 'France', 'approved', 'https://via.placeholder.com/400x200?text=Carpenter+Help');

-- Indexes for better performance
CREATE INDEX idx_actions_creator_id ON actions(creator_id);
CREATE INDEX idx_resources_publisher_id ON resources(publisher_id);
CREATE INDEX idx_actions_status ON actions(status);
CREATE INDEX idx_resources_status ON resources(status);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_actions_country ON actions(country);
CREATE INDEX idx_resources_country ON resources(country);
