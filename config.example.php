<?php
/**
 * ─── Configuration ─────────────────────────────────────────
 * Copy this file to config.php and fill in your real values.
 * DO NOT commit config.php to version control.
 */

// Database
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_USER', 'your_db_user');
define('DB_PASSWORD', 'your_db_password');
define('DB_NAME', 'your_db_name');
define('DB_CHARSET', 'utf8mb4');

// JWT
define('JWT_SECRET', 'change-this-to-a-random-64-char-string');
define('JWT_EXPIRES_IN', 365 * 24 * 60 * 60); // 365 days in seconds

// Admin (created on install)
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'change-this-password');

// Rate limiting
define('RATE_LIMIT_WINDOW', 15 * 60);
define('RATE_LIMIT_MAX', 1000);
define('AUTH_RATE_LIMIT_MAX', 20);

// CORS
define('CORS_ORIGIN', '*');
