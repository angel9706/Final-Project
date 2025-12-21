<?php
// Load PermissionHelper if not already loaded
if (!class_exists('App\Config\PermissionHelper')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

use App\Config\PermissionHelper;

// Initialize if needed
if (!PermissionHelper::isAuthenticated()) {
    PermissionHelper::init();
}

// Get current page from URL
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Get menu tree for current user
$menuTree = PermissionHelper::getMenuTree();
?>
<!-- Sidebar -->
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
            // Add separator before management and after menus
            if ($parentMenu['route'] === 'reports' || $parentMenu['route'] === 'menus'): ?>
                <hr class="my-4 border-gray-200">
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
</aside>

