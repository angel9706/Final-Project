<?php

namespace App\Config;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth
{
    private static $secretKey;

    public static function init()
    {
        self::$secretKey = $_ENV['JWT_SECRET'] ?? 'change-me-in-production';
    }

    /**
     * Generate JWT token
     * @param int $userId
     * @param string $email
     * @param string $role
     * @return string
     */
    public static function generateToken($userId, $email, $role = 'user')
    {
        if (!self::$secretKey) {
            self::init();
        }

        $issuedAt = time();
        $expire = $issuedAt + (int)($_ENV['JWT_EXPIRY'] ?? 86400); // 24 hours default

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'sub' => $userId,
            'email' => $email,
            'role' => $role
        ];

        return JWT::encode($payload, self::$secretKey, 'HS256');
    }

    /**
     * Verify and decode JWT token
     * @param string $token
     * @return object|false
     */
    public static function verifyToken($token)
    {
        if (!self::$secretKey) {
            self::init();
        }

        try {
            $decoded = JWT::decode($token, new Key(self::$secretKey, 'HS256'));
            return $decoded;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get token from Authorization header
     * @return string|null
     */
    public static function getTokenFromHeader()
    {
        $auth = null;
        
        // Try multiple ways to get Authorization header
        // Method 1: getallheaders()
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            // Check both cases (some servers return lowercase)
            if (isset($headers['Authorization'])) {
                $auth = $headers['Authorization'];
            } elseif (isset($headers['authorization'])) {
                $auth = $headers['authorization'];
            }
        }
        
        // Method 2: $_SERVER['HTTP_AUTHORIZATION']
        if (!$auth && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['HTTP_AUTHORIZATION'];
        }
        
        // Method 3: $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] (for Apache mod_rewrite)
        if (!$auth && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        
        // Method 4: Apache specific
        if (!$auth && function_exists('apache_request_headers')) {
            $apacheHeaders = apache_request_headers();
            if (isset($apacheHeaders['Authorization'])) {
                $auth = $apacheHeaders['Authorization'];
            } elseif (isset($apacheHeaders['authorization'])) {
                $auth = $apacheHeaders['authorization'];
            }
        }
        
        if ($auth && preg_match('/Bearer\s+(.+)/i', $auth, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Check if user is authenticated (supports both JWT and Session)
     * @return bool
     */
    public static function isAuthenticated()
    {
        // Check session first (new method)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            return true;
        }
        
        // Fallback to JWT token (old method)
        $token = self::getTokenFromHeader();
        return $token && self::verifyToken($token) !== false;
    }

    /**
     * Create user session (login)
     * @param int $userId
     * @param string $email
     * @param string $role
     * @param string $name
     */
    public static function login($userId, $email, $role = 'user', $name = '')
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $role;
        $_SESSION['user_name'] = $name;
    }

    /**
     * Destroy user session (logout)
     */
    public static function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
    }

    /**
     * Get current authenticated user (supports both JWT and Session)
     * @return object|false
     */
    public static function getCurrentUser()
    {
        // Check session first (new method)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            // Return session user data in same format as JWT
            return (object) [
                'sub' => $_SESSION['user_id'],
                'email' => $_SESSION['user_email'],
                'role' => $_SESSION['user_role'] ?? 'user',
                'name' => $_SESSION['user_name'] ?? ''
            ];
        }
        
        // Fallback to JWT token (old method)
        $token = self::getTokenFromHeader();
        
        if (!$token) {
            return false;
        }

        return self::verifyToken($token);
    }
}
