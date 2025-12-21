<?php

// Start session with proper cookie params
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/config/Env.php';
require_once __DIR__ . '/../src/config/Database.php';
require_once __DIR__ . '/../src/config/Auth.php';
require_once __DIR__ . '/../src/config/Router.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Env;
use App\Config\Auth;
use App\Config\Router;
use App\Config\Response;

// Load environment variables
Env::load();

// Initialize Auth
Auth::init();

// Handle CORS - allow credentials for session
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'http://localhost';
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Setup Router
$router = new Router();

// Authentication Routes
$router->post('/api/auth/register', 'AuthController@register');
$router->post('/api/auth/login', 'AuthController@login');
$router->get('/api/auth/verify', 'AuthController@verify');
$router->get('/api/auth/me', 'AuthController@me');
$router->post('/api/auth/logout', 'AuthController@logout');
$router->put('/api/auth/profile', 'AuthController@updateProfile');

// Monitoring Stations Routes (CRUD)
$router->get('/api/stations', 'StationController@index');
$router->get('/api/stations/show', 'StationController@show');
$router->post('/api/stations', 'StationController@store');
$router->put('/api/stations/update', 'StationController@update');
$router->delete('/api/stations/delete', 'StationController@delete');

// Air Quality Readings Routes (CRUD)
$router->get('/api/readings', 'ReadingController@index');
$router->get('/api/readings/show', 'ReadingController@show');
$router->get('/api/readings/by-station', 'ReadingController@byStation');
$router->get('/api/readings/trend', 'ReadingController@trend');
$router->post('/api/readings', 'ReadingController@store');
$router->put('/api/readings/update', 'ReadingController@update');
$router->delete('/api/readings/delete', 'ReadingController@delete');
$router->post('/api/readings/sync-aqicn', 'ReadingController@syncFromAqicn');

// Sync Station Routes
$router->post('/api/sync/station/{id}', 'ReadingController@syncStation');
$router->post('/api/sync/all-stations', 'ReadingController@syncAllStations');

// Chart Data Routes
$router->get('/api/charts/time-series', 'ReadingController@chartTimeSeries');
$router->get('/api/charts/status-distribution', 'ReadingController@chartStatusDistribution');
$router->get('/api/charts/statistics', 'ReadingController@chartStatistics');

// Analytics Routes
$router->get('/api/analytics/dashboard', 'AnalyticsController@dashboard');
$router->get('/api/analytics/daily-trend', 'AnalyticsController@dailyTrend');
$router->get('/api/analytics/hourly-trend', 'AnalyticsController@hourlyTrend');
$router->get('/api/analytics/compare-stations', 'AnalyticsController@compareStations');
$router->get('/api/analytics/user-activity', 'AnalyticsController@userActivity');
$router->get('/api/analytics/report', 'AnalyticsController@report');

// Notification Routes
$router->get('/api/notifications', 'NotificationController@getByUser');
$router->get('/api/notifications/unread', 'NotificationController@getUnread');
$router->post('/api/notifications/mark-read', 'NotificationController@markAsRead');
$router->post('/api/notifications/mark-all-read', 'NotificationController@markAllAsRead');
$router->post('/api/notifications/delete', 'NotificationController@delete');
$router->post('/api/notifications/subscribe-push', 'NotificationController@subscribePush');
$router->post('/api/notifications/unsubscribe-push', 'NotificationController@unsubscribePush');
$router->post('/api/notifications/subscribe', 'NotificationController@subscribePush');
$router->post('/api/notifications/unsubscribe', 'NotificationController@unsubscribePush');
$router->post('/api/notifications/test', 'NotificationController@sendTestNotification');
$router->post('/api/notifications/sync', 'NotificationController@syncNotifications');

// Push Notification Routes
$router->get('/api/push/vapid-public-key', function() {
    $pushConfig = new App\Config\PushNotification();
    Response::success(['public_key' => $pushConfig->getPublicKey()]);
});

// Report Routes
$router->post('/api/reports/generate', 'ReportController@generate');
$router->get('/api/reports/download', 'ReportController@download');

// User Management Routes (Admin Only)
$router->get('/api/users', 'UserManagementController@index');
$router->get('/api/users/show', 'UserManagementController@show');
$router->post('/api/users/store', 'UserManagementController@store');
$router->put('/api/users/update', 'UserManagementController@update');
$router->delete('/api/users/destroy', 'UserManagementController@destroy');
$router->get('/api/users/statistics', 'UserManagementController@statistics');
$router->get('/api/users/menu-access', 'UserManagementController@getMenuPermissions');
$router->put('/api/users/menu-access', 'UserManagementController@updateMenuPermissions');

// Menu Management Routes (Admin Only)
$router->get('/api/menus', 'MenuManagementController@index');
$router->get('/api/menus/show', 'MenuManagementController@show');
$router->get('/api/menus/parents', 'MenuManagementController@getParents');
$router->post('/api/menus/store', 'MenuManagementController@store');
$router->put('/api/menus/update', 'MenuManagementController@update');
$router->delete('/api/menus/destroy', 'MenuManagementController@destroy');
$router->post('/api/menus/reorder', 'MenuManagementController@reorder');
$router->get('/api/menus/statistics', 'MenuManagementController@statistics');

// Favorite Station Routes (User Only)
$router->get('/api/favorites', 'FavoriteStationController@index');
$router->get('/api/favorites/with-readings', 'FavoriteStationController@withReadings');
$router->get('/api/favorites/ids', 'FavoriteStationController@getFavoriteIds');
$router->get('/api/favorites/count', 'FavoriteStationController@count');
$router->post('/api/favorites', 'FavoriteStationController@store');
$router->post('/api/favorites/toggle', 'FavoriteStationController@toggle');
$router->delete('/api/favorites/delete', 'FavoriteStationController@destroy');

// Dashboard/Home Route
$router->get('/', function() {
    return Response::success([
        'app' => 'SIAPKAK',
        'name' => 'Sistem Information Air Pollution Kampus Area Karawang',
        'version' => '1.0.0',
        'endpoints' => [
            'auth' => '/api/auth/*',
            'stations' => '/api/stations/*',
            'readings' => '/api/readings/*',
            'reports' => '/api/reports/*'
        ]
    ]);
});

// Dispatch the request
$router->dispatch();
