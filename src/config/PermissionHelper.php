<?php
namespace App\Config;

use App\Config\Database;

class PermissionHelper {
    private static $db;
    private static $userRole;
    private static $menus;
    private static $initialized = false;

    /**
     * Initialize permission helper
     */
    public static function init() {
        if (self::$initialized) {
            return; // Already initialized
        }
        
        if (!isset($_SESSION)) {
            session_start();
        }

        try {
            self::$db = Database::getInstance()->getConnection();
            self::$userRole = $_SESSION['user_role'] ?? null;
            self::loadMenus();
            self::$initialized = true;
        } catch (\Exception $e) {
            // If database connection fails, still mark as initialized
            // to prevent infinite loops
            self::$initialized = true;
            error_log("PermissionHelper init failed: " . $e->getMessage());
        }
    }

    /**
     * Load all active menus from database
     */
    private static function loadMenus() {
        if (self::$menus === null && self::$db !== null) {
            try {
                $stmt = self::$db->query("
                    SELECT id, parent_id, title, icon, url, route, required_role, order_index
                    FROM menus 
                    WHERE is_active = 1 AND is_visible = 1
                    ORDER BY parent_id IS NULL DESC, order_index ASC
                ");
                self::$menus = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\Exception $e) {
                self::$menus = [];
                error_log("Failed to load menus: " . $e->getMessage());
            }
        }
    }

    /**
     * Check if user has access to a specific route
     */
    public static function hasAccess($route) {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        $userId = $_SESSION['user_id'];
        $userRole = self::$userRole ?? 'user';

        // Admin has access to everything
        if ($userRole === 'admin') {
            return true;
        }

        // Check user-specific menu access
        try {
            if (self::$db !== null) {
                $stmt = self::$db->prepare("
                    SELECT uma.can_access
                    FROM user_menu_access uma
                    JOIN menus m ON uma.menu_id = m.id
                    WHERE uma.user_id = ? AND m.route = ?
                    LIMIT 1
                ");
                $stmt->execute([$userId, $route]);
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if ($result !== false) {
                    return (bool)$result['can_access'];
                }
            }
        } catch (\Exception $e) {
            error_log("Error checking user menu access: " . $e->getMessage());
        }

        // Fallback: Check menu required_role
        if (self::$menus === null || empty(self::$menus)) {
            return false; // Deny access if menus not loaded and no specific permission
        }

        // Find menu by route
        foreach (self::$menus as $menu) {
            if ($menu['route'] === $route) {
                return $menu['required_role'] === 'all' || $menu['required_role'] === $userRole;
            }
        }

        // If route not found, deny access
        return false;
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }

    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated() {
        return isset($_SESSION['user_id']);
    }

    /**
     * Redirect if not authenticated
     */
    public static function requireAuth() {
        if (!self::isAuthenticated()) {
            header('Location: /siapkak/login');
            exit;
        }
    }

    /**
     * Redirect if not admin
     */
    public static function requireAdmin() {
        self::requireAuth();
        if (!self::isAdmin()) {
            header('Location: /siapkak/dashboard');
            exit;
        }
    }

    /**
     * Require access to specific route
     */
    public static function requireAccess($route) {
        self::requireAuth();
        if (!self::hasAccess($route)) {
            header('Location: /siapkak/dashboard');
            exit;
        }
    }

    /**
     * Get menus accessible by current user
     */
    public static function getAccessibleMenus() {
        if (!self::isAuthenticated()) {
            return [];
        }

        // Ensure menus are loaded
        if (self::$menus === null) {
            self::loadMenus();
        }

        if (empty(self::$menus)) {
            return [];
        }

        $userId = $_SESSION['user_id'];
        $userRole = self::$userRole ?? 'user';
        $accessibleMenus = [];

        // Admin has access to all menus
        if ($userRole === 'admin') {
            return self::$menus;
        }

        // For non-admin users, check user_menu_access table
        try {
            if (self::$db !== null) {
                $stmt = self::$db->prepare("
                    SELECT m.*
                    FROM menus m
                    LEFT JOIN user_menu_access uma ON m.id = uma.menu_id AND uma.user_id = ?
                    WHERE m.is_active = 1 AND m.is_visible = 1
                    AND (
                        uma.can_access = 1
                        OR m.required_role = 'all'
                        OR (uma.id IS NULL AND m.required_role = ?)
                    )
                    ORDER BY m.parent_id IS NULL DESC, m.order_index ASC
                ");
                $stmt->execute([$userId, $userRole]);
                return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }
        } catch (\Exception $e) {
            error_log("Error getting accessible menus: " . $e->getMessage());
        }

        // Fallback: use required_role from menu table
        foreach (self::$menus as $menu) {
            if ($menu['required_role'] === 'all' || $menu['required_role'] === $userRole) {
                $accessibleMenus[] = $menu;
            }
        }

        return $accessibleMenus;
    }

    /**
     * Build hierarchical menu structure
     */
    public static function getMenuTree() {
        $menus = self::getAccessibleMenus();
        
        if (empty($menus)) {
            return [];
        }
        
        $tree = [];

        // Build tree structure - first get parent menus
        foreach ($menus as $menu) {
            if ($menu['parent_id'] === null) {
                $menu['children'] = [];
                $tree[$menu['id']] = $menu;
            }
        }

        // Then add children to their parents
        foreach ($menus as $menu) {
            if ($menu['parent_id'] !== null && isset($tree[$menu['parent_id']])) {
                $tree[$menu['parent_id']]['children'][] = $menu;
            }
        }

        return array_values($tree);
    }

    /**
     * Get user info
     */
    public static function getUserInfo() {
        if (!self::isAuthenticated()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'] ?? null,
            'name' => $_SESSION['user_name'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'role' => $_SESSION['user_role'] ?? null
        ];
    }
}
