<?php

/**
 * Configuration and Database Connection
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'jobdz');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Get Database Connection
 * Returns a PDO database connection instance
 */
function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        date_default_timezone_set('Africa/Algiers');
        $pdo->exec("SET time_zone = '+01:00'");

        return $pdo;

    } catch (PDOException $e) {
        die('Database Connection Error: ' . $e->getMessage());
    }
}

// Global PDO connection
$pdo = getDBConnection();

/**
 * Escape output helper
 */
function e($str) {
    if (is_null($str)) {
        return '';
    }

    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/**
 * Escape HTML attributes
 */
function attr($str) {
    if (is_null($str)) {
        return '';
    }

    return htmlspecialchars((string)$str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Safe JSON encode
 */
function jsonEncode($data) {
    return htmlspecialchars(
        json_encode($data),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);