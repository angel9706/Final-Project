<?php

namespace App\Middlewares;

use App\Config\Auth;

class AuthMiddleware
{
    /**
     * Check if user is authenticated
     * @return bool
     */
    public static function authenticate()
    {
        return Auth::isAuthenticated();
    }

    /**
     * Require authentication, redirect to login if not
     */
    public static function requireAuth()
    {
        if (!self::authenticate()) {
            header('Location: /siapkak/login');
            exit();
        }
    }

    /**
     * Require specific role
     * @param string|array $roles
     */
    public static function requireRole($roles)
    {
        if (!self::authenticate()) {
            header('Location: /siapkak/login');
            exit();
        }

        $user = Auth::getCurrentUser();
        $allowedRoles = is_array($roles) ? $roles : [$roles];

        if (!in_array($user->role, $allowedRoles)) {
            http_response_code(403);
            die('Unauthorized');
        }
    }

    /**
     * Get authenticated user or null
     * @return object|null
     */
    public static function getUser()
    {
        return Auth::getCurrentUser() ?: null;
    }
}
