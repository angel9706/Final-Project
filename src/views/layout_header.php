<?php
// Initialize session and permission helper
if (!isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . '/../../vendor/autoload.php';
use App\Config\PermissionHelper;

// Initialize permission system
PermissionHelper::init();

// Get current page from URL
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$userInfo = PermissionHelper::getUserInfo();

// Get accessible menus
$menuTree = PermissionHelper::getMenuTree();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - SIAPKAK' : 'SIAPKAK - Sistem Informasi Air Pollution Kalimantan' ?></title>
    
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #a1a1a1; }
        
        /* Sidebar transition */
        .sidebar { transition: transform 0.3s ease-in-out; }
        .sidebar-overlay { transition: opacity 0.3s ease-in-out; }
        
        /* Accordion animation */
        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        .accordion-content.open {
            max-height: 500px;
        }
        .accordion-icon {
            transition: transform 0.3s ease;
        }
        .rotate-180 {
            transform: rotate(180deg);
        }
        
        /* Mobile sidebar */
        @media (max-width: 1023px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                top: 64px;
                left: 0;
                bottom: 0;
                z-index: 40;
            }
            .sidebar.open {
                transform: translateX(0);
            }
        }
        
        /* Loading spinner */
        .spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    <?php if (isset($additionalStyles)) echo $additionalStyles; ?>
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-white shadow-md fixed top-0 left-0 right-0 z-50">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <button id="sidebarToggle" class="lg:hidden p-2 rounded-md text-gray-600 hover:bg-gray-100 focus:outline-none">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <img src="/siapkak/public/img/logo.png" alt="SIAPKAK Logo" class="w-10 h-10 object-contain">
                    <h1 class="text-xl sm:text-2xl font-bold text-blue-600">SIAPKAK</h1>
                </div>
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <button id="notificationBtn" class="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-full">
                        <i class="fas fa-bell text-lg sm:text-xl"></i>
                        <span id="notificationBadge" class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center hidden">0</span>
                    </button>
                    <div class="hidden md:flex items-center space-x-3 border-l pl-4">
                        <div class="w-9 h-9 bg-gradient-to-br from-blue-500 to-blue-700 rounded-full flex items-center justify-center shadow">
                            <i class="fas fa-user text-white text-sm"></i>
                        </div>
                        <div>
                            <p id="userName" class="text-sm font-semibold text-gray-700"><?= htmlspecialchars($userInfo['name'] ?? 'User') ?></p>
                            <p id="userEmail" class="text-xs text-gray-500"><?= htmlspecialchars($userInfo['email'] ?? '') ?></p>
                        </div>
                    </div>
                    <button id="changePasswordBtn" class="p-2 sm:px-3 sm:py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition" title="Change Password">
                        <i class="fas fa-key"></i>
                        <span class="hidden sm:inline ml-1">Password</span>
                    </button>
                    <button id="logoutBtn" class="p-2 sm:px-3 sm:py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="hidden sm:inline ml-1">Logout</span>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar Overlay -->
    <div id="sidebarOverlay" class="sidebar-overlay fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

    <!-- Main Layout -->
    <div class="flex pt-16">
        <!-- Dynamic Sidebar -->
        <aside id="sidebar" class="sidebar w-64 bg-white shadow-lg h-[calc(100vh-64px)] overflow-y-auto lg:sticky lg:top-16 lg:translate-x-0">
            <nav class="p-4">
                <?php foreach ($menuTree as $parentMenu): ?>
                    <?php 
                    $hasChildren = !empty($parentMenu['children']);
                    $isActive = false;
                    
                    if ($hasChildren) {
                        foreach ($parentMenu['children'] as $child) {
                            if ($child['route'] === $currentPage) {
                                $isActive = true;
                                break;
                            }
                        }
                    } else {
                        $isActive = $parentMenu['route'] === $currentPage;
                    }
                    ?>
                    
                    <div class="mb-2">
                        <?php if ($hasChildren): ?>
                            <!-- Parent with children (accordion) -->
                            <button class="accordion-toggle w-full flex items-center justify-between px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition <?= $isActive ? 'bg-blue-50 text-blue-600' : '' ?>">
                                <span class="flex items-center">
                                    <i class="<?= htmlspecialchars($parentMenu['icon']) ?> mr-3"></i>
                                    <span class="font-medium"><?= htmlspecialchars($parentMenu['title']) ?></span>
                                </span>
                                <i class="fas fa-chevron-down text-xs transition-transform accordion-icon"></i>
                            </button>
                            <div class="accordion-content <?= $isActive ? 'open' : '' ?> pl-4">
                                <?php foreach ($parentMenu['children'] as $child): ?>
                                    <a href="<?= htmlspecialchars($child['url']) ?>" 
                                       class="nav-link flex items-center px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 rounded-lg mt-1 <?= $child['route'] === $currentPage ? 'active bg-blue-50 text-blue-600' : '' ?>">
                                        <i class="<?= htmlspecialchars($child['icon']) ?> mr-3 w-4"></i>
                                        <?= htmlspecialchars($child['title']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <!-- Single menu item -->
                            <a href="<?= htmlspecialchars($parentMenu['url']) ?>" 
                               class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition <?= $isActive ? 'bg-blue-50 text-blue-600' : '' ?>">
                                <i class="<?= htmlspecialchars($parentMenu['icon']) ?> mr-3"></i>
                                <span class="font-medium"><?= htmlspecialchars($parentMenu['title']) ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <?php 
                    // Add separator before management menu
                    if ($parentMenu['route'] === 'reports' || $parentMenu['route'] === 'menus'): ?>
                        <hr class="my-4 border-gray-200">
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 min-h-[calc(100vh-64px)] p-4 sm:p-6 lg:p-8">
