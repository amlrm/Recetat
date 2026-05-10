<?php
/**
 * Configuration File
 * All constants and settings for the application
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'nutrilife_db');
define('DB_PORT', 3306);

// API Keys (Use environment variables in production!)
define('SPOONACULAR_API_KEY', 'YOUR_API_KEY');
define('UNSPLASH_API_KEY', 'YOUR_UNSPLASH_KEY');

// Application Settings
define('APP_NAME', 'NutriLife');
define('APP_URL', 'http://localhost:8000');
define('TIMEZONE', 'UTC');

// Security
define('HASH_ALGORITHM', PASSWORD_BCRYPT);
define('HASH_OPTIONS', ['cost' => 12]);
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_TIMEOUT', 900); // 15 minutes

// File Upload
define('UPLOAD_DIR', __DIR__ . '/../../uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Email Configuration (optional)
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USER', 'your-email@gmail.com');
define('MAIL_PASS', 'your-app-password');

// Error Reporting - enable during development
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/error.log');

// Timezone
date_default_timezone_set(TIMEZONE);

// Session Configuration
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
session_set_cookie_params([
    'lifetime' => SESSION_TIMEOUT,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'] ?? '',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
