<?php
/**
 * Debug Permission System
 */
session_start();

echo "<h1>Debug Permission System</h1>";
echo "<hr>";

echo "<h2>Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Is Logged In:</h2>";
echo isset($_SESSION['user_id']) ? "YES - User ID: " . $_SESSION['user_id'] : "NO";
echo "<br>";

if (isset($_SESSION['user_id'])) {
    echo "<h2>User Info:</h2>";
    echo "Name: " . ($_SESSION['user_name'] ?? 'N/A') . "<br>";
    echo "Email: " . ($_SESSION['user_email'] ?? 'N/A') . "<br>";
    echo "Role: " . ($_SESSION['user_role'] ?? 'N/A') . "<br>";
    
    // Test PermissionHelper
    require_once __DIR__ . '/../vendor/autoload.php';
    
    try {
        \App\Config\PermissionHelper::init();
        
        echo "<h2>Permission Helper Status:</h2>";
        echo "Is Authenticated: " . (\App\Config\PermissionHelper::isAuthenticated() ? "YES" : "NO") . "<br>";
        echo "Is Admin: " . (\App\Config\PermissionHelper::isAdmin() ? "YES" : "NO") . "<br>";
        
        echo "<h2>Access Check:</h2>";
        $routes = ['dashboard', 'analytics', 'stations', 'readings', 'reports', 'users', 'menus', 'settings'];
        foreach ($routes as $route) {
            $hasAccess = \App\Config\PermissionHelper::hasAccess($route);
            echo "$route: " . ($hasAccess ? "✅ YES" : "❌ NO") . "<br>";
        }
        
        echo "<h2>Accessible Menus:</h2>";
        $menus = \App\Config\PermissionHelper::getAccessibleMenus();
        echo "Total menus accessible: " . count($menus) . "<br>";
        echo "<pre>";
        print_r($menus);
        echo "</pre>";
        
        echo "<h2>Menu Tree:</h2>";
        $tree = \App\Config\PermissionHelper::getMenuTree();
        echo "<pre>";
        print_r($tree);
        echo "</pre>";
        
    } catch (Exception $e) {
        echo "<h2>ERROR:</h2>";
        echo "<pre>" . $e->getMessage() . "</pre>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "<br><br>";
    echo "<a href='/siapkak/login'>Login here</a>";
}
