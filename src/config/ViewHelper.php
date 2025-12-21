<?php
/**
 * SIAPKAK View Helper Functions
 * 
 * Centralized view loader dan helper functions
 * Usage:
 * require_once __DIR__ . '/../config/ViewHelper.php';
 * 
 * renderPage('Page Title', $content);
 * includeComponent('forms');
 */

class ViewHelper {
    private static $basePath;
    private static $viewPath;
    
    /**
     * Initialize view paths
     */
    public static function init($basePath = __DIR__) {
        self::$basePath = $basePath;
        self::$viewPath = dirname($basePath) . '/views';
    }
    
    /**
     * Include a view file
     */
    public static function include($component) {
        $file = self::$viewPath . '/' . $component . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
        throw new Exception("View component not found: $component");
    }
    
    /**
     * Render full page with header & footer
     */
    public static function renderPage($title, $content = '') {
        self::include('header');
        echo '<div class="page-content">' . $content . '</div>';
        self::include('footer');
    }
    
    /**
     * Get view file path
     */
    public static function getPath($component) {
        return self::$viewPath . '/' . $component . '.php';
    }
    
    /**
     * Check if view exists
     */
    public static function exists($component) {
        return file_exists(self::getPath($component));
    }
}

// Initialize on load
ViewHelper::init(__DIR__);

/**
 * Convenience functions for common views
 */

function renderPage($title, $content = '') {
    return ViewHelper::renderPage($title, $content);
}

function includeComponent($component) {
    return ViewHelper::include($component);
}

function includeHeader($pageTitle = '') {
    ViewHelper::include('header');
}

function includeFooter() {
    ViewHelper::include('footer');
}

function includeAlerts() {
    ViewHelper::include('alerts');
}

function includeForms() {
    ViewHelper::include('forms');
}

function includeModals() {
    ViewHelper::include('modals');
}

function includeComponents() {
    ViewHelper::include('components');
}

/**
 * Data display helpers
 */

function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function formatTime($time) {
    return date('H:i:s', strtotime($time));
}

function formatDateTime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

function formatAQI($aqi) {
    $aqi = (int)$aqi;
    if ($aqi <= 50) return 'Good';
    if ($aqi <= 100) return 'Moderate';
    if ($aqi <= 150) return 'Unhealthy for Sensitive Groups';
    if ($aqi <= 200) return 'Unhealthy';
    if ($aqi <= 300) return 'Very Unhealthy';
    return 'Hazardous';
}

function getAQIColor($aqi) {
    $aqi = (int)$aqi;
    if ($aqi <= 50) return 'bg-green-100 text-green-800';
    if ($aqi <= 100) return 'bg-yellow-100 text-yellow-800';
    if ($aqi <= 150) return 'bg-orange-100 text-orange-800';
    if ($aqi <= 200) return 'bg-red-100 text-red-800';
    if ($aqi <= 300) return 'bg-purple-100 text-purple-800';
    return 'bg-gray-100 text-gray-800';
}

function getAQIBadgeColor($aqi) {
    $aqi = (int)$aqi;
    if ($aqi <= 50) return 'success';
    if ($aqi <= 100) return 'info';
    if ($aqi <= 150) return 'warning';
    if ($aqi <= 200) return 'danger';
    return 'danger';
}

/**
 * Error handling
 */

function renderErrorPage($code = 404, $message = 'Page not found') {
    includeHeader("Error $code");
    ?>
    <div class="text-center py-12">
        <h1 class="text-6xl font-bold text-red-600 mb-4"><?php echo $code; ?></h1>
        <p class="text-xl text-gray-600 mb-8"><?php echo htmlspecialchars($message); ?></p>
        <a href="/siapkak/public/" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
            Back to Home
        </a>
    </div>
    <?php
    includeFooter();
}

/**
 * Authentication helpers
 */

function isLoggedIn() {
    return !empty($_SESSION['user_id'] ?? null);
}

function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /siapkak/public/index.html');
        exit;
    }
}

function requireRole($role) {
    if (!isLoggedIn() || getCurrentUser()['role'] !== $role) {
        header('HTTP/1.0 403 Forbidden');
        renderErrorPage(403, 'Access denied');
        exit;
    }
}

/**
 * Response helpers
 */

function jsonResponse($success, $data = [], $message = '') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

function jsonSuccess($data = [], $message = 'Success') {
    jsonResponse(true, $data, $message);
}

function jsonError($message = 'Error', $data = []) {
    jsonResponse(false, $data, $message);
}
