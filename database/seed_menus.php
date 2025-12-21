<?php
/**
 * SIAPKAK - Seed All Menus
 * Script untuk memasukkan semua menu ke database dan memberikan akses penuh untuk admin
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;

try {
    // Get database connection
    $db = Database::getInstance()->getConnection();
    
    echo "===========================================\n";
    echo "SIAPKAK - Menu Seeder\n";
    echo "===========================================\n\n";

    // Start transaction
    $db->beginTransaction();

    // Clear existing menus
    echo "Menghapus menu yang ada...\n";
    $db->exec("DELETE FROM menus");
    $db->exec("ALTER TABLE menus AUTO_INCREMENT = 1");
    echo "✓ Menu lama berhasil dihapus\n\n";

    // Insert parent menus
    echo "Memasukkan menu parent...\n";
    $parentMenus = [
        [
            'title' => 'Dashboard',
            'icon' => 'fas fa-home',
            'url' => '#',
            'route' => 'dashboard',
            'order_index' => 1,
            'required_role' => 'all',
            'description' => 'Dashboard menu group'
        ],
        [
            'title' => 'Monitoring',
            'icon' => 'fas fa-broadcast-tower',
            'url' => '#',
            'route' => 'monitoring',
            'order_index' => 2,
            'required_role' => 'all',
            'description' => 'Monitoring menu group'
        ],
        [
            'title' => 'Laporan',
            'icon' => 'fas fa-file-alt',
            'url' => '/siapkak/reports',
            'route' => 'reports',
            'order_index' => 3,
            'required_role' => 'admin',
            'description' => 'Reports and exports'
        ],
        [
            'title' => 'Manajemen',
            'icon' => 'fas fa-cogs',
            'url' => '#',
            'route' => 'management',
            'order_index' => 4,
            'required_role' => 'admin',
            'description' => 'Management menu group'
        ],
        [
            'title' => 'Pengaturan',
            'icon' => 'fas fa-cog',
            'url' => '/siapkak/settings',
            'route' => 'settings',
            'order_index' => 5,
            'required_role' => 'admin',
            'description' => 'System settings'
        ]
    ];

    $parentIds = [];
    $stmt = $db->prepare("
        INSERT INTO menus (parent_id, title, icon, url, route, order_index, is_active, is_visible, required_role, description) 
        VALUES (NULL, ?, ?, ?, ?, ?, 1, 1, ?, ?)
    ");

    foreach ($parentMenus as $menu) {
        $stmt->execute([
            $menu['title'],
            $menu['icon'],
            $menu['url'],
            $menu['route'],
            $menu['order_index'],
            $menu['required_role'],
            $menu['description']
        ]);
        $parentIds[$menu['title']] = $db->lastInsertId();
        echo "  ✓ {$menu['title']} (ID: {$parentIds[$menu['title']]})\n";
    }
    echo "✓ Menu parent berhasil dimasukkan\n\n";

    // Insert child menus
    echo "Memasukkan sub-menu...\n";
    $childMenus = [
        // Dashboard children
        [
            'parent' => 'Dashboard',
            'title' => 'Overview',
            'icon' => 'fas fa-chart-line',
            'url' => '/siapkak/dashboard',
            'route' => 'dashboard',
            'order_index' => 1,
            'required_role' => 'all',
            'description' => 'Dashboard overview'
        ],
        [
            'parent' => 'Dashboard',
            'title' => 'Analytics',
            'icon' => 'fas fa-chart-pie',
            'url' => '/siapkak/analytics',
            'route' => 'analytics',
            'order_index' => 2,
            'required_role' => 'all',
            'description' => 'Analytics page'
        ],
        // Monitoring children
        [
            'parent' => 'Monitoring',
            'title' => 'Stasiun',
            'icon' => 'fas fa-map-marker-alt',
            'url' => '/siapkak/stations',
            'route' => 'stations',
            'order_index' => 1,
            'required_role' => 'all',
            'description' => 'Monitoring stations management'
        ],
        [
            'parent' => 'Monitoring',
            'title' => 'Data Readings',
            'icon' => 'fas fa-database',
            'url' => '/siapkak/readings',
            'route' => 'readings',
            'order_index' => 2,
            'required_role' => 'all',
            'description' => 'Air quality readings data'
        ],
        // Management children
        [
            'parent' => 'Manajemen',
            'title' => 'User Management',
            'icon' => 'fas fa-users',
            'url' => '/siapkak/users',
            'route' => 'users',
            'order_index' => 1,
            'required_role' => 'admin',
            'description' => 'User management'
        ],
        [
            'parent' => 'Manajemen',
            'title' => 'Menu Management',
            'icon' => 'fas fa-bars',
            'url' => '/siapkak/menus',
            'route' => 'menus',
            'order_index' => 2,
            'required_role' => 'admin',
            'description' => 'Menu management'
        ]
    ];

    $stmt = $db->prepare("
        INSERT INTO menus (parent_id, title, icon, url, route, order_index, is_active, is_visible, required_role, description) 
        VALUES (?, ?, ?, ?, ?, ?, 1, 1, ?, ?)
    ");

    foreach ($childMenus as $menu) {
        $parentId = $parentIds[$menu['parent']];
        $stmt->execute([
            $parentId,
            $menu['title'],
            $menu['icon'],
            $menu['url'],
            $menu['route'],
            $menu['order_index'],
            $menu['required_role'],
            $menu['description']
        ]);
        echo "  ✓ {$menu['parent']} > {$menu['title']} (ID: {$db->lastInsertId()})\n";
    }
    echo "✓ Sub-menu berhasil dimasukkan\n\n";

    // Display summary
    echo "\n===========================================\n";
    echo "RINGKASAN\n";
    echo "===========================================\n";
    
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN parent_id IS NULL THEN 1 ELSE 0 END) as parents,
            SUM(CASE WHEN parent_id IS NOT NULL THEN 1 ELSE 0 END) as children,
            SUM(CASE WHEN required_role = 'admin' THEN 1 ELSE 0 END) as admin_only,
            SUM(CASE WHEN required_role = 'all' THEN 1 ELSE 0 END) as all_users
        FROM menus
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Total Menu      : {$stats['total']}\n";
    echo "Menu Parent     : {$stats['parents']}\n";
    echo "Sub-menu        : {$stats['children']}\n";
    echo "Admin Only      : {$stats['admin_only']}\n";
    echo "Semua User      : {$stats['all_users']}\n";
    
    echo "\n===========================================\n";
    echo "AKSES MENU BERDASARKAN ROLE\n";
    echo "===========================================\n";
    
    // Display admin access
    echo "\n[ADMIN] - Akses Penuh ke Semua Menu:\n";
    $stmt = $db->query("
        SELECT m1.title as parent, m2.title as child, m2.url
        FROM menus m1
        LEFT JOIN menus m2 ON m2.parent_id = m1.id
        WHERE m1.parent_id IS NULL
        ORDER BY m1.order_index, m2.order_index
    ");
    
    $currentParent = '';
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['parent'] !== $currentParent) {
            $currentParent = $row['parent'];
            echo "\n  {$row['parent']}\n";
        }
        if ($row['child']) {
            echo "    └─ {$row['child']} ({$row['url']})\n";
        }
    }
    
    // Display user access
    echo "\n[USER] - Akses Menu:\n";
    $stmt = $db->query("
        SELECT m1.title as parent, m2.title as child, m2.url
        FROM menus m1
        LEFT JOIN menus m2 ON m2.parent_id = m1.id AND m2.required_role = 'all'
        WHERE m1.parent_id IS NULL AND m1.required_role = 'all'
        ORDER BY m1.order_index, m2.order_index
    ");
    
    $currentParent = '';
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['parent'] !== $currentParent) {
            $currentParent = $row['parent'];
            echo "\n  {$row['parent']}\n";
        }
        if ($row['child']) {
            echo "    └─ {$row['child']} ({$row['url']})\n";
        }
    }
    
    echo "\n===========================================\n";
    echo "✓ Seeder berhasil dijalankan!\n";
    echo "===========================================\n\n";
    
    echo "CATATAN:\n";
    echo "- Admin memiliki akses PENUH ke semua menu\n";
    echo "- User biasa hanya dapat akses menu dengan role 'all'\n";
    echo "- Menu 'Manajemen' dan sub-menunya hanya untuk admin\n";
    echo "- Semua menu sudah aktif dan visible\n\n";

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}
