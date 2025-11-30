<?php
// config/config.php - Database configuration and application constants

// Define DEVELOPMENT_MODE for AuthHelper - set to true for development, false for production
if (!defined('DEVELOPMENT_MODE')) {
    define('DEVELOPMENT_MODE', true);  // Change to false in production
}

// Resend Email Service API Key
if (!defined('RESEND_API_KEY')) {
    $resendApiKey = getenv('RESEND_API_KEY') ?: 're_HUSan4tf_Hj6D8e9AhT4V5G6NFgANWpBb'; // Updated to use the new API key

    if (empty($resendApiKey) && defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE === true) {
        // In development mode, we can use a placeholder or skip email functionality
        $resendApiKey = '';
        error_log("WARNING: RESEND_API_KEY environment variable is not set. Email functionality may not work in development mode.");
    } elseif (empty($resendApiKey)) {
        // In production mode, fail fast with clear error
        error_log("ERROR: RESEND_API_KEY environment variable is required but not set.");
        die("Configuration error: RESEND_API_KEY environment variable is missing.");
    }

    define('RESEND_API_KEY', $resendApiKey);
}

class Config {
    private static $pdo = null;

    public static function getConnexion() {
        if (!isset(self::$pdo)) {
            $servername = "127.0.0.1"; // localhost works too
            $port = 3307;               // <--- specify the correct port
            $username = "root";
            $password = "";
            $dbname = "connect_for_peace";

            try {
                self::$pdo = new PDO(
                    "mysql:host=$servername;port=$port;dbname=$dbname;charset=utf8mb4",
                    $username,
                    $password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                    ]
                );

                // Test connection
                self::$pdo->query('SELECT 1');

            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());

                if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE === true) {
                    die("Database Error: " . htmlspecialchars($e->getMessage()));
                } else {
                    die("Database connection error. Please contact support.");
                }
            }
        }

        return self::$pdo;
    }
}