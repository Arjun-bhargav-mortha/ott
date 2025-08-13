<?php
/**
 * Database Configuration
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'ott_streaming');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Site configuration
define('SITE_URL', 'http://localhost');
define('SITE_NAME', 'StreamFlix Pro');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

// Security
define('SECRET_KEY', 'your-secret-key-change-this-in-production');
define('SESSION_TIMEOUT', 86400); // 24 hours

// File upload limits
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Streaming settings
define('HLS_SEGMENT_DURATION', 10);
define('MAX_BITRATE', 5000); // kbps
define('DEFAULT_PLAYER_QUALITY', 'auto');

// Error reporting (disable in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Timezone
date_default_timezone_set('UTC');