<?php
// utils/AuthHelper.php
// Authentication helper that supports both real and mock authentication

class AuthHelper {
    private static $pdo;

    public static function setConnection($pdo) {
        self::$pdo = $pdo;
    }

    // Initialize PDO connection if not already set
    private static function initializePdo() {
        if (!self::$pdo) {
            // If PDO connection isn't set, try to get it from config
            if (class_exists('Config')) {
                self::$pdo = Config::getConnexion();
            }
        }
    }

    // Get current user based on session, URL parameter for testing, or default
    public static function getCurrentUser($userIdOverride = null) {
        // Start session if not already started
        if (session_id() == '') {
            session_start();
        }

        // First, check if user data is stored in session
        if (isset($_SESSION['user'])) {
            return $_SESSION['user'];
        }

        // Check for development mode configuration
        $isDevelopment = defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE === true;

        // For development/testing purposes, check URL for override
        $testUserId = null;
        if ($isDevelopment) {
            $testUserId = $userIdOverride ?? $_GET['user_id'] ?? null;
        }

        if ($testUserId) {
            // Try to get real user data from database first
            $user = self::getUserById($testUserId);

            if (!$user) {
                // Testing mode - return mock user based on ID
                if ($testUserId == 1) {
                    $user = [
                        'id' => 1,
                        'name' => 'Admin User',
                        'email' => 'admin@connectforpeace.com',
                        'role' => 'admin',
                        'avatar_url' => 'https://api.placeholder.com/40/40?text=AU',
                        'badge' => 'Administrator'
                    ];
                } else {
                    $user = [
                        'id' => 2,
                        'name' => 'Regular User',
                        'email' => 'user@connectforpeace.com',
                        'role' => 'user',
                        'avatar_url' => 'https://api.placeholder.com/40/40?text=RU',
                        'badge' => 'Community Member'
                    ];
                }
            }

            return $user;
        }

        // If no session data and no URL override, return default user
        // This is for cases where the user is not explicitly authenticated
        return [
            'id' => null, // No authenticated user
            'name' => 'Guest User',
            'email' => null,
            'role' => 'guest',
            'avatar_url' => 'https://api.placeholder.com/40/40?text=GU',
            'badge' => 'Guest'
        ];
    }

    // Check if current user has admin privileges
    public static function isAdmin($user = null) {
        $currentUser = $user ?: self::getCurrentUser();
        return $currentUser['role'] === 'admin';
    }

    // Check if current user owns specific content
    public static function isOwner($contentOwnerId, $userId = null) {
        $currentUser = self::getCurrentUser();
        $actualUserId = $userId ?: $currentUser['id'];

        // If no authenticated user, return false
        if ($actualUserId === null) {
            return false;
        }

        return (int)$contentOwnerId === (int)$actualUserId;
    }

    // Check if user is logged in
    public static function isLoggedIn() {
        if (session_id() == '') {
            session_start();
        }

        return isset($_SESSION['user_id']) && $_SESSION['user_id'] !== null;
    }

    // Start a new session for the given user
    public static function startSession($userId) {
        if (session_id() == '') {
            session_start();
        }

        // Try to get real user data from database first
        $user = self::getUserById($userId);

        if (!$user) {
            // Fallback to mock data if user doesn't exist in DB
            if ($userId == 1) {
                $user = [
                    'id' => 1,
                    'name' => 'Admin User',
                    'email' => 'admin@connectforpeace.com',
                    'role' => 'admin',
                    'avatar_url' => 'https://api.placeholder.com/40/40?text=AU',
                    'badge' => 'Administrator'
                ];
            } else {
                $user = [
                    'id' => 2,
                    'name' => 'Regular User',
                    'email' => 'user@connectforpeace.com',
                    'role' => 'user',
                    'avatar_url' => 'https://api.placeholder.com/40/40?text=RU',
                    'badge' => 'Community Member'
                ];
            }
        }

        // Store user object in session
        $_SESSION['user'] = $user;
        $_SESSION['user_id'] = $user['id'];

        return $user;
    }

    // Get user by ID from database if available
    private static function getUserById($userId) {
        self::initializePdo(); // Initialize PDO if not already set

        if (self::$pdo) {
            try {
                $stmt = self::$pdo->prepare("SELECT id, name, email, role, avatar_url FROM users WHERE id = :id");
                $stmt->execute([':id' => $userId]);
                $userData = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($userData) {
                    return [
                        'id' => $userData['id'],
                        'name' => $userData['name'],
                        'email' => $userData['email'],
                        'role' => $userData['role'],
                        'avatar_url' => $userData['avatar_url'],
                        'badge' => $userData['role'] === 'admin' ? 'Administrator' : 'Community Member'
                    ];
                }
            } catch (Exception $e) {
                error_log("Error fetching user by ID: " . $e->getMessage());
            }
        }

        return null;
    }

    // Destroy the current session
    public static function destroySession() {
        if (session_id() == '') {
            session_start();
        }

        // Unset all session variables
        $_SESSION = array();

        // If session was started with cookies, delete the cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Destroy the session
        session_destroy();
    }
}