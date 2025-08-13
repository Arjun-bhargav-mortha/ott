<?php
/**
 * Authentication System
 */

class Auth {
    private static $db;
    
    public static function init() {
        self::$db = getDB();
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
                self::logout();
            }
        }
        $_SESSION['last_activity'] = time();
    }
    
    public static function login($email, $password) {
        $user = self::$db->fetch(
            "SELECT id, email, password_hash, first_name, last_name, role, status FROM users WHERE email = ? AND status = 'active'",
            [$email]
        );
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            
            // Update last login
            self::$db->execute("UPDATE users SET updated_at = NOW() WHERE id = ?", [$user['id']]);
            
            return true;
        }
        
        return false;
    }
    
    public static function register($email, $password, $firstName, $lastName) {
        // Check if email exists
        $existing = self::$db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        try {
            self::$db->beginTransaction();
            
            // Create user
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            self::$db->execute(
                "INSERT INTO users (email, password_hash, first_name, last_name) VALUES (?, ?, ?, ?)",
                [$email, $passwordHash, $firstName, $lastName]
            );
            
            $userId = self::$db->lastInsertId();
            
            // Create default profile
            self::$db->execute(
                "INSERT INTO profiles (user_id, name, is_default) VALUES (?, ?, TRUE)",
                [$userId, $firstName]
            );
            
            self::$db->commit();
            return ['success' => true, 'message' => 'Registration successful'];
        } catch (Exception $e) {
            self::$db->rollback();
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }
    
    public static function logout() {
        session_destroy();
        header('Location: /login.php');
        exit;
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: /login.php');
            exit;
        }
    }
    
    public static function requireAdmin() {
        self::requireLogin();
        if ($_SESSION['user_role'] !== 'admin') {
            header('Location: /');
            exit;
        }
    }
    
    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public static function getUserRole() {
        return $_SESSION['user_role'] ?? 'user';
    }
    
    public static function getCurrentProfile() {
        if (!isset($_SESSION['profile_id'])) {
            // Get default profile
            $profile = self::$db->fetch(
                "SELECT id, name, avatar, maturity_rating, language FROM profiles WHERE user_id = ? AND is_default = TRUE",
                [self::getUserId()]
            );
            if ($profile) {
                $_SESSION['profile_id'] = $profile['id'];
                $_SESSION['profile_name'] = $profile['name'];
                $_SESSION['profile_avatar'] = $profile['avatar'];
            }
        }
        
        return [
            'id' => $_SESSION['profile_id'] ?? null,
            'name' => $_SESSION['profile_name'] ?? '',
            'avatar' => $_SESSION['profile_avatar'] ?? 'default-avatar.png'
        ];
    }
    
    public static function switchProfile($profileId) {
        $profile = self::$db->fetch(
            "SELECT id, name, avatar, maturity_rating, language FROM profiles WHERE id = ? AND user_id = ?",
            [$profileId, self::getUserId()]
        );
        
        if ($profile) {
            $_SESSION['profile_id'] = $profile['id'];
            $_SESSION['profile_name'] = $profile['name'];
            $_SESSION['profile_avatar'] = $profile['avatar'];
            return true;
        }
        
        return false;
    }
    
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}