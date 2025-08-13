<?php
/**
 * Utility Functions
 */

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Format duration in minutes to human readable
 */
function formatDuration($minutes) {
    if ($minutes < 60) {
        return $minutes . ' min';
    }
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return $hours . 'h' . ($mins > 0 ? ' ' . $mins . 'm' : '');
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Get time ago string
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}

/**
 * Generate thumbnail URL
 */
function getThumbnailUrl($imagePath, $width = 300, $height = 200) {
    if (empty($imagePath) || !file_exists($imagePath)) {
        return '/assets/images/placeholder-thumbnail.jpg';
    }
    return $imagePath;
}

/**
 * Get poster URL
 */
function getPosterUrl($posterPath) {
    if (empty($posterPath)) {
        return '/assets/images/placeholder-poster.jpg';
    }
    if (filter_var($posterPath, FILTER_VALIDATE_URL)) {
        return $posterPath;
    }
    return UPLOAD_URL . 'posters/' . $posterPath;
}

/**
 * Get backdrop URL
 */
function getBackdropUrl($backdropPath) {
    if (empty($backdropPath)) {
        return '/assets/images/placeholder-backdrop.jpg';
    }
    if (filter_var($backdropPath, FILTER_VALIDATE_URL)) {
        return $backdropPath;
    }
    return UPLOAD_URL . 'backdrops/' . $backdropPath;
}

/**
 * Encrypt sensitive data
 */
function encryptData($data) {
    $key = hash('sha256', SECRET_KEY);
    $iv = substr(hash('sha256', SECRET_KEY), 0, 16);
    return base64_encode(openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv));
}

/**
 * Decrypt sensitive data
 */
function decryptData($data) {
    $key = hash('sha256', SECRET_KEY);
    $iv = substr(hash('sha256', SECRET_KEY), 0, 16);
    return openssl_decrypt(base64_decode($data), 'aes-256-cbc', $key, 0, $iv);
}

/**
 * Log activity
 */
function logActivity($action, $details = '', $userId = null) {
    $logFile = __DIR__ . '/../logs/activity.log';
    $userId = $userId ?: (Auth::getUserId() ?: 'anonymous');
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $logEntry = "[$timestamp] User: $userId | IP: $ip | Action: $action | Details: $details" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Generate random string
 */
function generateRandomString($length = 16) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Validate M3U URL
 */
function validateM3UUrl($url) {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    
    // Check if URL is accessible
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'method' => 'HEAD'
        ]
    ]);
    
    $headers = @get_headers($url, 1, $context);
    return $headers && strpos($headers[0], '200') !== false;
}

/**
 * Get client IP address
 */
function getClientIP() {
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            if ($key === 'HTTP_X_FORWARDED_FOR') {
                $ip = explode(',', $ip)[0];
            }
            if (filter_var(trim($ip), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return trim($ip);
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Rate limiting
 */
function checkRateLimit($action, $limit = 5, $window = 300) {
    $ip = getClientIP();
    $key = $action . '_' . $ip;
    $file = __DIR__ . '/../cache/rate_limit_' . md5($key) . '.json';
    
    $data = ['count' => 0, 'reset_time' => time() + $window];
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if ($data['reset_time'] <= time()) {
            $data = ['count' => 0, 'reset_time' => time() + $window];
        }
    }
    
    $data['count']++;
    file_put_contents($file, json_encode($data));
    
    return $data['count'] <= $limit;
}