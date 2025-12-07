-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Dec 07, 2025 at 11:14 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `integrated_version`
--

-- --------------------------------------------------------

--
-- Table structure for table `actions`
--

CREATE TABLE `actions` (
  `id` int(10) UNSIGNED NOT NULL,
  `creator_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `theme` varchar(100) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `status` enum('pending','approved','rejected','active','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  `image_url` varchar(500) DEFAULT 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEFjdGlvbiBJbWFnZTwvdGV4dD48L3N2Zz4=',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `actions`
--

INSERT INTO `actions` (`id`, `creator_id`, `title`, `description`, `category`, `theme`, `location`, `country`, `latitude`, `longitude`, `start_time`, `end_time`, `status`, `image_url`, `created_at`, `updated_at`) VALUES
(1, 1, 'Community Garden Cleanup', 'Join us for a day of cleaning and maintaining our local community garden. Bring gloves and a positive attitude!', 'Environment', 'Cleanup', 'Central Park, Paris', 'France', NULL, NULL, '2025-01-15 10:00:00', '2025-01-15 14:00:00', 'approved', 'https://images.unsplash.com/photo-1542838132-92c53300491e?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80', '2025-12-06 09:07:13', '2025-12-06 09:07:13'),
(2, 1, 'Educational Workshop', 'Learn about sustainable living practices and how to create eco-friendly habits at home.', 'Education', 'Workshop', 'Public Library, Paris', 'France', NULL, NULL, '2025-01-20 14:00:00', '2025-01-20 16:00:00', 'approved', 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80', '2025-12-06 09:07:13', '2025-12-06 09:07:13'),
(3, 2, 'Social Support Meetup', 'Monthly meetup for local social support initiatives and sharing best practices.', 'Social Help', 'Mentoring', 'Community Center, Lyon', 'France', NULL, NULL, '2025-01-25 16:00:00', '2025-01-25 18:00:00', 'approved', 'https://images.unsplash.com/photo-1558591718-50d24a5b2c33?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80', '2025-12-06 09:07:13', '2025-12-06 09:07:13'),
(4, 1, 'rayen', 'test reminderrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrr', 'Environment', 'Cleanup', 'Tunisia - (36.8547, 10.2068)', 'Tunisia', 36.85472882, 10.20681381, '2025-12-20 16:20:00', '2025-12-20 18:20:00', 'approved', '../uploads/actions/693449e94e78a_1765034473.png', '2025-12-06 15:21:13', '2025-12-06 15:21:24');

-- --------------------------------------------------------

--
-- Table structure for table `action_participants`
--

CREATE TABLE `action_participants` (
  `id` int(10) UNSIGNED NOT NULL,
  `action_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `action_participants`
--

INSERT INTO `action_participants` (`id`, `action_id`, `user_id`, `joined_at`, `created_at`) VALUES
(3, 1, 1, '2025-12-06 14:28:57', '2025-12-06 14:28:57'),
(4, 4, 1, '2025-12-06 15:23:02', '2025-12-06 15:23:02');

-- --------------------------------------------------------

--
-- Table structure for table `ban_logs`
--

CREATE TABLE `ban_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `banned_by` int(10) UNSIGNED NOT NULL,
  `reason` text DEFAULT NULL,
  `banned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `action_id` int(10) UNSIGNED DEFAULT NULL,
  `resource_id` int(10) UNSIGNED DEFAULT NULL,
  `story_id` int(10) UNSIGNED DEFAULT NULL,
  `content` text NOT NULL,
  `status` enum('active','flagged','deleted') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `user_id`, `action_id`, `resource_id`, `story_id`, `content`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, NULL, 'll', 'active', '2025-12-06 09:11:05', '2025-12-06 09:11:05'),
(2, 1, NULL, NULL, 2, '11', 'active', '2025-12-06 09:49:50', '2025-12-06 09:49:50'),
(3, 1, NULL, NULL, 2, '11', 'active', '2025-12-06 09:49:55', '2025-12-06 09:49:55'),
(4, 1, NULL, NULL, 1, 'hh', 'active', '2025-12-06 10:50:18', '2025-12-06 10:50:18'),
(5, 1, NULL, NULL, 1, 'hh', 'active', '2025-12-06 10:50:21', '2025-12-06 10:50:21'),
(6, 1, NULL, NULL, 2, '1', 'active', '2025-12-06 12:02:16', '2025-12-06 12:02:16'),
(7, 1, NULL, NULL, 2, '1', 'active', '2025-12-06 12:02:17', '2025-12-06 12:02:17'),
(8, 1, NULL, NULL, 1, 'This is a clean comment', 'active', '2025-12-06 14:20:54', '2025-12-06 14:20:54'),
(9, 1, NULL, NULL, 1, 'This is damn annoying', 'active', '2025-12-06 14:20:54', '2025-12-06 14:20:54'),
(10, 1, NULL, NULL, 1, 'This contains cunt word', 'active', '2025-12-06 14:20:54', '2025-12-06 14:20:54'),
(11, 1, NULL, NULL, 1, 'This shit will kill you', 'active', '2025-12-06 14:20:54', '2025-12-06 14:20:54'),
(12, 1, NULL, NULL, 1, 'You are a total asshole', 'active', '2025-12-06 14:20:54', '2025-12-06 14:20:54'),
(13, 1, NULL, NULL, 2, 'vv', 'active', '2025-12-06 14:29:05', '2025-12-06 14:29:05'),
(14, 1, NULL, NULL, 2, 'f', 'active', '2025-12-06 14:29:11', '2025-12-06 14:29:11'),
(15, 1, NULL, NULL, 1, 'ff', 'active', '2025-12-06 14:29:17', '2025-12-06 14:29:17'),
(16, 1, NULL, NULL, 1, 'ff', 'active', '2025-12-06 14:29:31', '2025-12-06 14:29:31'),
(17, 1, NULL, NULL, 2, 'gg', 'active', '2025-12-06 15:27:30', '2025-12-06 15:27:30'),
(18, 1, NULL, 1, NULL, 'jj', 'active', '2025-12-06 16:12:46', '2025-12-06 16:12:46'),
(19, 1, 1, NULL, NULL, 'gg', 'active', '2025-12-06 16:12:55', '2025-12-06 16:12:55'),
(20, 1, NULL, NULL, 2, 'jj', 'active', '2025-12-07 09:18:42', '2025-12-07 09:18:42'),
(21, 1, NULL, NULL, 2, 'ii', 'active', '2025-12-07 09:44:56', '2025-12-07 09:44:56');

-- --------------------------------------------------------

--
-- Table structure for table `content_violations`
--

CREATE TABLE `content_violations` (
  `id` int(10) UNSIGNED NOT NULL,
  `content_type` enum('story','comment','action','resource') NOT NULL,
  `content_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `flagged_word` varchar(100) NOT NULL,
  `word_category` varchar(50) NOT NULL,
  `severity` varchar(20) NOT NULL,
  `status` enum('pending','reviewed','dismissed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `flagged_words`
--

CREATE TABLE `flagged_words` (
  `id` int(10) UNSIGNED NOT NULL,
  `word` varchar(100) NOT NULL,
  `category` enum('hate_speech','profanity','harassment','spam','other') NOT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `auto_action` enum('flag','reject') NOT NULL DEFAULT 'flag',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `flagged_words`
--

INSERT INTO `flagged_words` (`id`, `word`, `category`, `severity`, `auto_action`, `created_at`) VALUES
(1, 'badword1', 'profanity', 'low', 'flag', '2025-12-06 14:13:33'),
(2, 'hateword1', 'hate_speech', 'critical', 'reject', '2025-12-06 14:13:33'),
(3, 'spamlink', 'spam', 'high', 'reject', '2025-12-06 14:13:33'),
(4, 'harass', 'harassment', 'medium', 'flag', '2025-12-06 14:13:34');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` enum('action_created','resource_created','story_created','action_updated','resource_updated','story_updated','action_deleted','resource_deleted','story_deleted','action_approved','resource_approved','story_approved','action_rejected','resource_rejected','story_rejected','reminder','report_created') NOT NULL,
  `message` text NOT NULL,
  `related_id` int(10) UNSIGNED DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `message`, `related_id`, `is_read`, `created_at`) VALUES
(1, 1, 'report_created', 'New user report submitted for action ID: 1', 1, 0, '2025-12-06 09:11:12'),
(2, 2, '', 'Admin User commented on your story \'Building Bridges Between Communities\'.', 2, 0, '2025-12-06 09:49:51'),
(3, 2, '', 'Admin User commented on your story \'Building Bridges Between Communities\'.', 2, 0, '2025-12-06 09:49:55'),
(4, 2, '', 'Admin User commented on your story \'Building Bridges Between Communities\'.', 2, 0, '2025-12-06 12:02:17'),
(5, 2, '', 'Admin User commented on your story \'Building Bridges Between Communities\'.', 2, 0, '2025-12-06 12:02:17'),
(6, 2, '', 'Admin User commented on your story \'Building Bridges Between Communities\'.', 2, 0, '2025-12-06 14:29:05'),
(7, 2, '', 'Admin User commented on your story \'Building Bridges Between Communities\'.', 2, 0, '2025-12-06 14:29:11'),
(8, 1, 'report_created', 'New user report submitted for story ID: 1', 1, 0, '2025-12-06 14:42:58'),
(9, 1, 'action_created', 'New action \'rayen\' has been submitted for approval by user ID 1.', 4, 0, '2025-12-06 15:21:13'),
(10, 1, 'action_created', 'Your action \'rayen\' has been created successfully and is pending approval.', 0, 0, '2025-12-06 15:21:13'),
(11, 1, 'action_approved', 'Your action \'rayen\' has been approved.', 4, 0, '2025-12-06 15:21:24'),
(12, 1, 'action_approved', 'Your action \'rayen\' has been approved.', 4, 0, '2025-12-06 15:21:43'),
(13, 1, 'action_approved', 'Your action \'rayen\' has been approved.', 4, 0, '2025-12-06 15:21:48'),
(14, 1, 'action_approved', 'Your action \'rayen\' has been approved.', 4, 0, '2025-12-06 15:21:51'),
(15, 1, 'action_approved', 'Your action \'rayen\' has been approved.', 4, 0, '2025-12-06 15:21:55'),
(16, 1, 'action_approved', 'Your action \'rayen\' has been approved.', 4, 0, '2025-12-06 15:21:58'),
(17, 1, 'action_approved', 'Your action \'rayen\' has been approved.', 4, 0, '2025-12-06 15:22:02'),
(18, 1, 'action_approved', 'Your action \'rayen\' has been approved.', 4, 0, '2025-12-06 15:22:06'),
(19, 1, 'action_approved', 'Your action \'rayen\' has been approved.', 4, 0, '2025-12-06 15:22:10'),
(20, 1, 'action_approved', 'Your action \'rayen\' has been approved.', 4, 0, '2025-12-06 15:22:15'),
(21, 1, 'action_approved', 'Your action \'rayen\' has been approved.', 4, 0, '2025-12-06 15:22:20'),
(22, 1, 'action_approved', 'Your action \'rayen\' has been approved.', 4, 0, '2025-12-06 15:22:45'),
(23, 1, 'report_created', 'New user report submitted for action ID: 4', 4, 1, '2025-12-06 15:23:12'),
(24, 2, '', 'Admin User commented on your story \'Building Bridges Between Communities\'.', 2, 0, '2025-12-06 15:27:30'),
(25, 1, 'report_created', 'New user report submitted for story ID: 2', 2, 1, '2025-12-06 15:29:13'),
(26, 2, '', 'Admin User commented on your story \'Building Bridges Between Communities\'.', 2, 0, '2025-12-07 09:18:43'),
(27, 2, '', 'Admin User commented on your story \'Building Bridges Between Communities\'.', 2, 0, '2025-12-07 09:44:56');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`) VALUES
(1, 'rayen12x@gmail.com', '83382dfc3b2350213e4c8f67d7766154f143997ca7610a33610b2d63d62a1154', '2025-12-06 17:24:38', '2025-12-06 15:24:38'),
(2, 'rayen12x@gmail.com', 'ee58f10387abde179fa0a17a3293e43f89286153e48c41db3143830fbcc1ef7a', '2025-12-06 17:26:19', '2025-12-06 15:26:19');

-- --------------------------------------------------------

--
-- Table structure for table `reminders`
--

CREATE TABLE `reminders` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `item_type` enum('action','resource','story') NOT NULL,
  `reminder_time` datetime NOT NULL,
  `reminder_type` enum('email','in_app','both') DEFAULT 'both',
  `sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reminders`
--

INSERT INTO `reminders` (`id`, `user_id`, `item_id`, `item_type`, `reminder_time`, `reminder_type`, `sent`, `created_at`) VALUES
(1, 1, 4, 'action', '2025-12-19 16:20:00', 'both', 0, '2025-12-06 15:21:13'),
(2, 1, 4, 'action', '2025-12-20 14:20:00', 'both', 0, '2025-12-06 15:22:59');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(10) UNSIGNED NOT NULL,
  `reporter_id` int(10) UNSIGNED NOT NULL,
  `reported_item_id` int(10) UNSIGNED NOT NULL,
  `reported_item_type` enum('action','resource','story','comment','user') NOT NULL,
  `report_category` enum('scam','spam','inappropriate','fake','hate_speech','harassment','other') NOT NULL,
  `report_reason` text NOT NULL,
  `status` enum('pending','reviewed','resolved','dismissed') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `reporter_id`, `reported_item_id`, `reported_item_type`, `report_category`, `report_reason`, `status`, `admin_notes`, `reviewed_by`, `reviewed_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'action', 'scam', 'll', 'pending', NULL, NULL, NULL, '2025-12-06 09:11:12', '2025-12-06 09:11:12'),
(2, 1, 1, 'story', 'spam', 'misleading', 'pending', NULL, NULL, NULL, '2025-12-06 14:42:58', '2025-12-06 14:42:58'),
(3, 1, 4, 'action', 'fake', 'fuck you', 'pending', NULL, NULL, NULL, '2025-12-06 15:23:12', '2025-12-06 15:23:12'),
(4, 1, 2, 'story', 'hate_speech', 'ff', 'pending', NULL, NULL, NULL, '2025-12-06 15:29:12', '2025-12-06 15:29:12');

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `id` int(10) UNSIGNED NOT NULL,
  `publisher_id` int(10) UNSIGNED NOT NULL,
  `resource_name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `type` enum('offer','request','knowledge') NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` enum('pending','approved','rejected','available','claimed') NOT NULL DEFAULT 'pending',
  `image_url` varchar(500) DEFAULT 'https://via.placeholder.com/400x200?text=Resource+Image',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resources`
--

INSERT INTO `resources` (`id`, `publisher_id`, `resource_name`, `description`, `type`, `category`, `location`, `country`, `latitude`, `longitude`, `status`, `image_url`, `created_at`, `updated_at`) VALUES
(1, 1, 'School Books Collection', 'Complete set of school books for children aged 6-12, in excellent condition.', 'offer', 'Books', 'Paris Libraries Network', 'France', 48.85660000, 2.35220000, 'approved', 'https://images.unsplash.com/photo-1535905557558-afc487d8eccd?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80', '2025-12-06 09:07:13', '2025-12-06 09:07:13'),
(2, 1, 'Furniture Donation', 'Unused furniture including sofa, dining table and chairs available for donation.', 'offer', 'Furniture', 'Charitable Organizations, France', 'France', 48.85660000, 2.35220000, 'approved', 'https://images.unsplash.com/photo-1513694203232-719a20d9c6c7?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80', '2025-12-06 09:07:13', '2025-12-06 09:07:13'),
(3, 2, 'Volunteer Teaching Help', 'Looking for volunteers to teach basic computer skills to seniors.', 'request', 'Knowledge', 'Senior Centers, Lyon', 'France', 45.76400000, 4.83570000, 'approved', 'https://images.unsplash.com/photo-1522202178754-37bc9c1d7ed2?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80', '2025-12-06 09:07:13', '2025-12-06 09:07:13');

-- --------------------------------------------------------

--
-- Table structure for table `stories`
--

CREATE TABLE `stories` (
  `id` int(10) UNSIGNED NOT NULL,
  `creator_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `excerpt` text DEFAULT NULL,
  `author_name` varchar(255) NOT NULL,
  `author_avatar` varchar(10) DEFAULT 'ST',
  `theme` varchar(100) DEFAULT NULL,
  `language` varchar(50) DEFAULT 'en',
  `privacy` enum('public','private') DEFAULT 'public',
  `status` enum('pending','approved','rejected','published','draft','archived') NOT NULL DEFAULT 'pending',
  `image_url` varchar(500) DEFAULT 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIFN0b3J5IEltYWdlPC90ZXh0Pjwvc3ZnPg==',
  `views` int(10) UNSIGNED DEFAULT 0,
  `reactions_count` int(10) UNSIGNED DEFAULT 0,
  `comments_count` int(10) UNSIGNED DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stories`
--

INSERT INTO `stories` (`id`, `creator_id`, `title`, `content`, `excerpt`, `author_name`, `author_avatar`, `theme`, `language`, `privacy`, `status`, `image_url`, `views`, `reactions_count`, `comments_count`, `created_at`, `updated_at`) VALUES
(1, 1, 'My Journey to Peace', 'Join me as I share my personal journey towards finding peace and reconciliation in my community...', 'Personal account of finding peace through community involvement...', 'Alice Johnson', 'AJ', 'Personal', 'en', 'public', 'approved', 'https://images.unsplash.com/photo-1507842217343-583bb7270b66?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80', 0, 0, 0, '2025-12-06 09:07:13', '2025-12-06 09:07:13'),
(2, 2, 'Building Bridges Between Communities', 'A heartfelt story about connecting different communities through small acts of kindness...', 'How small gestures can create meaningful connections between communities...', 'Bob Martin', 'BM', 'Community', 'en', 'public', 'approved', 'https://images.unsplash.com/photo-1529338296759-201ce48d7e9b?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80', 0, 0, 0, '2025-12-06 09:07:13', '2025-12-06 09:07:13'),
(3, 1, 'Art as Healing', 'Exploring how creative expression can serve as a healing force in times of conflict...', 'Using art as a therapeutic tool for recovery and peace building...', 'Alice Johnson', 'AJ', 'Art & Culture', 'en', 'public', 'approved', 'https://images.unsplash.com/photo-1579546929662-711aa81148cf?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80', 0, 0, 0, '2025-12-06 09:07:13', '2025-12-07 09:59:59');

-- --------------------------------------------------------

--
-- Table structure for table `story_reactions`
--

CREATE TABLE `story_reactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `story_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `reaction_type` enum('heart','support','inspiration','solidarity') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `story_reactions`
--

INSERT INTO `story_reactions` (`id`, `story_id`, `user_id`, `reaction_type`, `created_at`) VALUES
(67, 1, 1, 'inspiration', '2025-12-06 14:44:08'),
(68, 2, 1, 'inspiration', '2025-12-06 15:27:32'),
(69, 2, 1, 'support', '2025-12-06 15:35:59'),
(71, 2, 2, 'heart', '2025-12-06 16:03:46'),
(72, 2, 2, 'support', '2025-12-06 16:03:47'),
(73, 2, 2, 'inspiration', '2025-12-06 16:03:48'),
(74, 2, 2, 'solidarity', '2025-12-06 16:03:48'),
(77, 2, 1, 'heart', '2025-12-07 09:18:41'),
(78, 2, 1, 'solidarity', '2025-12-07 09:44:59');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `badge` varchar(100) DEFAULT 'Community Member',
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `country` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `password_reset_token`, `password_reset_expires`, `avatar_url`, `badge`, `role`, `country`, `created_at`, `updated_at`, `is_active`) VALUES
(1, 'Admin User', 'admin@connectforpeace.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'https://api.dicebear.com/7.x/avataaars/svg?seed=admin', 'Administrator', 'admin', NULL, '2025-12-06 09:07:13', '2025-12-06 09:07:13', 1),
(2, 'Regular User', 'rayen12x@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'https://api.dicebear.com/7.x/avataaars/svg?seed=regular', 'Community Member', 'user', NULL, '2025-12-06 09:07:13', '2025-12-06 09:07:13', 1),
(3, 'Emma Wilson', 'emma@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'https://api.dicebear.com/7.x/avataaars/svg?seed=emma', 'Volunteer Coordinator', 'user', 'Australia', '2025-12-07 09:20:08', '2025-12-07 09:20:08', 1),
(4, 'James Brown', 'james@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'https://api.dicebear.com/7.x/avataaars/svg?seed=james', 'Environment Advocate', 'user', 'United Kingdom', '2025-12-07 09:20:08', '2025-12-07 09:20:08', 1),
(5, 'Lina Ahmed', 'lina@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'https://api.dicebear.com/7.x/avataaars/svg?seed=lina', 'Cultural Bridge', 'user', 'Egypt', '2025-12-07 09:20:08', '2025-12-07 09:20:08', 1),
(6, 'Carlos Rodriguez', 'carlos@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'https://api.dicebear.com/7.x/avataaars/svg?seed=carlos', 'Community Builder', 'user', 'Mexico', '2025-12-07 09:20:08', '2025-12-07 09:20:08', 1),
(7, 'Yuki Tanaka', 'yuki@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'https://api.dicebear.com/7.x/avataaars/svg?seed=yuki', 'Tech Mentor', 'user', 'Japan', '2025-12-07 09:20:08', '2025-12-07 09:20:08', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `actions`
--
ALTER TABLE `actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_actions_creator_id` (`creator_id`),
  ADD KEY `idx_actions_status` (`status`),
  ADD KEY `idx_actions_country` (`country`);

--
-- Indexes for table `action_participants`
--
ALTER TABLE `action_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_participation` (`action_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `ban_logs`
--
ALTER TABLE `ban_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `banned_by` (`banned_by`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_comments_user_id` (`user_id`),
  ADD KEY `idx_comments_action_id` (`action_id`),
  ADD KEY `idx_comments_resource_id` (`resource_id`),
  ADD KEY `idx_comments_story_id` (`story_id`);

--
-- Indexes for table `content_violations`
--
ALTER TABLE `content_violations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `flagged_words`
--
ALTER TABLE `flagged_words`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `word` (`word`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user_id` (`user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `reminders`
--
ALTER TABLE `reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_item` (`item_id`,`item_type`),
  ADD KEY `idx_reminder_time` (`reminder_time`),
  ADD KEY `idx_sent` (`sent`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `idx_reports_reporter_id` (`reporter_id`),
  ADD KEY `idx_reports_status` (`status`);

--
-- Indexes for table `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_resources_publisher_id` (`publisher_id`),
  ADD KEY `idx_resources_status` (`status`),
  ADD KEY `idx_resources_country` (`country`);

--
-- Indexes for table `stories`
--
ALTER TABLE `stories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_stories_creator_id` (`creator_id`),
  ADD KEY `idx_stories_status` (`status`),
  ADD KEY `idx_stories_theme` (`theme`);

--
-- Indexes for table `story_reactions`
--
ALTER TABLE `story_reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_story_reaction` (`story_id`,`user_id`,`reaction_type`),
  ADD KEY `idx_story_id` (`story_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `actions`
--
ALTER TABLE `actions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `action_participants`
--
ALTER TABLE `action_participants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ban_logs`
--
ALTER TABLE `ban_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content_violations`
--
ALTER TABLE `content_violations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `flagged_words`
--
ALTER TABLE `flagged_words`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reminders`
--
ALTER TABLE `reminders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `stories`
--
ALTER TABLE `stories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `story_reactions`
--
ALTER TABLE `story_reactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `actions`
--
ALTER TABLE `actions`
  ADD CONSTRAINT `actions_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `action_participants`
--
ALTER TABLE `action_participants`
  ADD CONSTRAINT `action_participants_ibfk_1` FOREIGN KEY (`action_id`) REFERENCES `actions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `action_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ban_logs`
--
ALTER TABLE `ban_logs`
  ADD CONSTRAINT `ban_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ban_logs_ibfk_2` FOREIGN KEY (`banned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`action_id`) REFERENCES `actions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_3` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_4` FOREIGN KEY (`story_id`) REFERENCES `stories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_violations`
--
ALTER TABLE `content_violations`
  ADD CONSTRAINT `content_violations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reminders`
--
ALTER TABLE `reminders`
  ADD CONSTRAINT `reminders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `resources`
--
ALTER TABLE `resources`
  ADD CONSTRAINT `resources_ibfk_1` FOREIGN KEY (`publisher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stories`
--
ALTER TABLE `stories`
  ADD CONSTRAINT `stories_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `story_reactions`
--
ALTER TABLE `story_reactions`
  ADD CONSTRAINT `story_reactions_ibfk_1` FOREIGN KEY (`story_id`) REFERENCES `stories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `story_reactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
