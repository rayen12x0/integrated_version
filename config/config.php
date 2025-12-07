<?php
// config/config.php - Database configuration and application constants

// Define DEVELOPMENT_MODE for AuthHelper - set to true for development, false for production
if (!defined('DEVELOPMENT_MODE')) {
    define('DEVELOPMENT_MODE', true);  // Change to false in production
}

// SMTP Configuration for PHPMailer
if (!defined('SMTP_HOST')) {
    $smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
    define('SMTP_HOST', $smtpHost);
}

if (!defined('SMTP_USERNAME')) {
    $smtpUsername = getenv('SMTP_USERNAME') ?: 'rayen12x@gmail.com';
    define('SMTP_USERNAME', $smtpUsername);
}

if (!defined('SMTP_PASSWORD')) {
    $smtpPassword = getenv('SMTP_PASSWORD') ?: 'zrhg ompy upwq qatc';
    define('SMTP_PASSWORD', $smtpPassword);
}

if (!defined('SMTP_PORT')) {
    $smtpPort = (int)(getenv('SMTP_PORT') ?: 587);
    define('SMTP_PORT', $smtpPort);
}

if (!defined('SMTP_FROM_EMAIL')) {
    $smtpFromEmail = getenv('SMTP_FROM_EMAIL') ?: 'noreply@connectforpeace.com';
    define('SMTP_FROM_EMAIL', $smtpFromEmail);
}

if (!defined('SMTP_FROM_NAME')) {
    $smtpFromName = getenv('SMTP_FROM_NAME') ?: 'Connect for Peace';
    define('SMTP_FROM_NAME', $smtpFromName);
}

class Config {
    private static $pdo = null;

    public static function getConnexion() {
        if (!isset(self::$pdo)) {
            $servername = "127.0.0.1"; // localhost works too
            $port = 3306;               // <--- specify the correct port (XAMPP default)
            $username = "root";
            $password = "";
            $dbname = "integrated_version";

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