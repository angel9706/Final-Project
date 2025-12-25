<?php
/**
 * Root index.php - Route handler
 * 
 * Routes requests to appropriate handlers
 */

$uri = $_SERVER['REQUEST_URI'];
$basePath = '/siapkak';

// Remove base path from URI
$cleanUri = str_replace($basePath, '', $uri);
$cleanUri = parse_url($cleanUri, PHP_URL_PATH);

// Handle API request (deteksi jenis request)
if (strpos($cleanUri, '/api/') === 0) {
    require_once __DIR__ . '/public/index.php';
    exit();
}

// Handle auth request (session-based PHP auth)
if (strpos($cleanUri, '/auth/') === 0) {
    $authFile = __DIR__ . '/public' . $cleanUri;
    if (file_exists($authFile)) {
        require_once $authFile;
        exit();
    }
}

// Handle page routes (halamam)
$routes = [
    '/dashboard' => 'dashboard.php',
    '/reports' => 'reports.php',
    '/stations' => 'stations.php',
    '/readings' => 'readings.php',
    '/analytics' => 'analytics.php',
    '/settings' => 'settings.php',
    '/users' => 'users.php',
    '/menus' => 'menus.php'
];

foreach ($routes as $route => $file) {
    if ($cleanUri === $route || $cleanUri === $route . '/') {
        $pageFile = __DIR__ . '/public/' . $file;
        if (file_exists($pageFile)) {
            require_once $pageFile;
            exit();
        }
    }
}

// Handle /reports route
if ($cleanUri === '/reports' || $cleanUri === '/reports/') {
    $reportsPage = __DIR__ . '/public/reports.html';
    if (file_exists($reportsPage)) {
        header('Content-Type: text/html');
        readfile($reportsPage);
        exit();
    }
}

// Check if it's a static file in public folder
$publicFile = __DIR__ . '/public' . $cleanUri;
if (file_exists($publicFile) && is_file($publicFile)) {
    // Serve static file with correct content type
    $ext = pathinfo($publicFile, PATHINFO_EXTENSION);
    $contentTypes = [
        'html' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
    ];
    
    if (isset($contentTypes[$ext])) {
        header('Content-Type: ' . $contentTypes[$ext]);
    }
    
    readfile($publicFile);
    exit();
}

// Default: serve login page for root path
$loginPage = __DIR__ . '/public/login.php';
if (file_exists($loginPage)) {
    require_once $loginPage;
    exit();
}

// Fallback
echo 'SIAPKAK - System Error';
?>
