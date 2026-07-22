<?php
/**
 * Database
 * Singleton PDO wrapper.
 */
class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $port = defined('DB_PORT') && DB_PORT ? ';port=' . DB_PORT : '';
            $dsn = 'mysql:host=' . DB_HOST . $port . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());
                http_response_code(500);
                die('Database connection failed. Please check your configuration in config/config.php.');
            }
        }

        return self::$instance;
    }

    private function __clone()
    {
    }
}
