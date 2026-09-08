<?php
/**
 * Database connection wrapper using PDO
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $connection = null;

    public static function connect() {
        if (self::$connection === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                self::$connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // If database connection fails, show a user-friendly error page instead of raw crash
                echo "<div style='font-family: Arial, sans-serif; text-align: center; padding: 50px; background-color: #f8f9fa;'>";
                echo "<h1 style='color: #dc3545;'>Database Connection Error</h1>";
                echo "<p style='color: #6c757d;'>We are having trouble connecting to our system database. Please ensure MySQL is running and the database details are correct.</p>";
                echo "<div style='margin-top: 20px; padding: 15px; border: 1px solid #dee2e6; display: inline-block; background-color: #fff; text-align: left;'>";
                echo "<strong>Error Message:</strong> " . htmlspecialchars($e->getMessage());
                echo "</div>";
                echo "<br><br><a href='#' onclick='window.location.reload();' style='background-color: #198754; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Retry Connection</a>";
                echo "</div>";
                exit();
            }
        }
        return self::$connection;
    }
}
