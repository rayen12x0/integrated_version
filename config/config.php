<?php
// config/config.php - Database configuration and application constants

// Define DEVELOPMENT_MODE for AuthHelper - set to true for development, false for production
if (!defined('DEVELOPMENT_MODE')) {
    define('DEVELOPMENT_MODE', true);  // Change to false in production
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
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );
            } catch (PDOException $e) {
                error_log("Database connection error: " . $e->getMessage());
                throw $e;
            }
        }

        return self::$pdo;
    }
}