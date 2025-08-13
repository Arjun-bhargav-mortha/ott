<?php
/**
 * Authentication System (File-based)
 */

class Auth {
    private static $storage;
    
    public static function init() {
        self::$storage = getStorage();
        
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
        $users = self::$storage->read('users');
        $user = null;
        
        foreach ($users as $u) {
            if ($u['email'] === $email && $u['status'] === 'active') {
                $user = $u;
                break;
            }
        }
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            
            // Update last login in file
            self::$storage->update('users', function($u) use ($user) {
                return $u['id'] === $user['id'];
            }, ['updated_at' => date('Y-m-d H:i:s')]);
            
            return true;
        }
        
        return false;
    }
    
    public static function register($email, $password, $firstName, $lastName) {
        $users = self::$storage->read('users');
        $existing = null;
        
        foreach ($users as $user) {
            if ($user['email'] === $email) {
                $existing = $user;
                break;
            }
        }
        
        if ($existing) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        try {
            $userId = time() . rand(1000, 9999); // Simple ID generation
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            $newUser = [
                'id' => $userId,
                'email' => $email,
                'password_hash' => $passwordHash,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'role' => 'user',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            self::$storage->append('users', $newUser);
            
            // Create default profile
            $newProfile = [
                'id' => time() . rand(1000, 9999),
                'user_id' => $userId,
                'name' => $firstName,
                'avatar' => 'default-avatar.png',
                'maturity_rating' => 'all',
                'language' => 'en',
                'is_default' => true,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            self::$storage->append('profiles', $newProfile);
            
            return ['success' => true, 'message' => 'Registration successful'];
        } catch (Exception $e) {
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
            $profiles = self::$storage->read('profiles');
            $profile = null;
            
            foreach ($profiles as $p) {
                if ($p['user_id'] == self::getUserId() && $p['is_default']) {
                    $profile = $p;
                    break;
                }
            }
            
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
        $profiles = self::$storage->read('profiles');
        $profile = null;
        
        foreach ($profiles as $p) {
            if ($p['id'] == $profileId && $p['user_id'] == self::getUserId()) {
                $profile = $p;
                break;
            }
        }
        
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