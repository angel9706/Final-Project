<?php
namespace App\Controllers;

use App\Config\Database;
use App\Config\Response;

class MenuManagementController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all menus in hierarchical structure
     */
    public function index() {
        try {
            $sql = "SELECT m.*, 
                    (SELECT COUNT(*) FROM menus WHERE parent_id = m.id) as children_count,
                    (SELECT title FROM menus WHERE id = m.parent_id) as parent_name
                    FROM menus m 
                    ORDER BY m.order_index ASC, m.id ASC";
            
            $stmt = $this->db->query($sql);
            $menus = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Build hierarchical structure
            $menuTree = $this->buildMenuTree($menus);

            Response::json([
                'success' => true,
                'data' => $menus
            ]);

        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'message' => 'Error fetching menus: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single menu by ID
     */
    public function show() {
        try {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

            if ($id <= 0) {
                Response::json([
                    'success' => false,
                    'message' => 'Invalid menu ID'
                ], 400);
                return;
            }

            $stmt = $this->db->prepare("SELECT * FROM menus WHERE id = ?");
            $stmt->execute([$id]);
            $menu = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$menu) {
                Response::json([
                    'success' => false,
                    'message' => 'Menu not found'
                ], 404);
                return;
            }

            Response::json([
                'success' => true,
                'data' => $menu
            ]);

        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'message' => 'Error fetching menu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get parent menus for dropdown
     */
    public function getParents() {
        try {
            $sql = "SELECT id, title FROM menus WHERE parent_id IS NULL ORDER BY order_index ASC";
            $stmt = $this->db->query($sql);
            $parents = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            Response::json([
                'success' => true,
                'data' => $parents
            ]);

        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'message' => 'Error fetching parent menus: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new menu
     */
    public function store() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // Validation
            $errors = $this->validateMenu($data);
            if (!empty($errors)) {
                Response::json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ], 400);
                return;
            }

            // Get next order index
            $orderIndex = $this->getNextOrderIndex($data['parent_id'] ?? null);

            // Insert menu
            $sql = "INSERT INTO menus (parent_id, title, icon, url, route, order_index, is_active, is_visible, required_role, description) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['parent_id'] ?? null,
                $data['title'],
                $data['icon'] ?? 'fas fa-circle',
                $data['url'],
                $data['route'],
                $data['order_index'] ?? $orderIndex,
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                isset($data['is_visible']) ? (int)$data['is_visible'] : 1,
                $data['required_role'] ?? 'all',
                $data['description'] ?? null
            ]);

            $menuId = $this->db->lastInsertId();

            Response::json([
                'success' => true,
                'message' => 'Menu created successfully',
                'data' => ['id' => $menuId]
            ], 201);

        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'message' => 'Error creating menu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update existing menu
     */
    public function update() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = isset($data['id']) ? (int)$data['id'] : 0;

            if ($id <= 0) {
                Response::json([
                    'success' => false,
                    'message' => 'Invalid menu ID'
                ], 400);
                return;
            }

            // Check if menu exists
            $stmt = $this->db->prepare("SELECT id FROM menus WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                Response::json([
                    'success' => false,
                    'message' => 'Menu not found'
                ], 404);
                return;
            }

            // Validation
            $errors = $this->validateMenu($data);
            if (!empty($errors)) {
                Response::json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ], 400);
                return;
            }

            // Prevent circular reference
            if (isset($data['parent_id']) && $data['parent_id'] == $id) {
                Response::json([
                    'success' => false,
                    'message' => 'Menu cannot be its own parent'
                ], 400);
                return;
            }

            // Update menu
            $sql = "UPDATE menus SET 
                    parent_id = ?, 
                    title = ?, 
                    icon = ?, 
                    url = ?, 
                    route = ?, 
                    order_index = ?, 
                    is_active = ?, 
                    is_visible = ?, 
                    required_role = ?, 
                    description = ? 
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['parent_id'] ?? null,
                $data['title'],
                $data['icon'] ?? 'fas fa-circle',
                $data['url'],
                $data['route'],
                $data['order_index'] ?? 0,
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                isset($data['is_visible']) ? (int)$data['is_visible'] : 1,
                $data['required_role'] ?? 'all',
                $data['description'] ?? null,
                $id
            ]);

            Response::json([
                'success' => true,
                'message' => 'Menu updated successfully'
            ]);

        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'message' => 'Error updating menu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete menu
     */
    public function destroy() {
        try {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

            if ($id <= 0) {
                Response::json([
                    'success' => false,
                    'message' => 'Invalid menu ID'
                ], 400);
                return;
            }

            // Check if menu has children
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM menus WHERE parent_id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                Response::json([
                    'success' => false,
                    'message' => 'Cannot delete menu with sub-menus. Please delete sub-menus first.'
                ], 400);
                return;
            }

            // Delete menu
            $stmt = $this->db->prepare("DELETE FROM menus WHERE id = ?");
            $stmt->execute([$id]);

            Response::json([
                'success' => true,
                'message' => 'Menu deleted successfully'
            ]);

        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'message' => 'Error deleting menu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reorder menus
     */
    public function reorder() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['menus']) || !is_array($data['menus'])) {
                Response::json([
                    'success' => false,
                    'message' => 'Invalid menu order data'
                ], 400);
                return;
            }

            $this->db->beginTransaction();

            foreach ($data['menus'] as $menu) {
                if (isset($menu['id']) && isset($menu['order_index'])) {
                    $stmt = $this->db->prepare("UPDATE menus SET order_index = ? WHERE id = ?");
                    $stmt->execute([$menu['order_index'], $menu['id']]);
                }
            }

            $this->db->commit();

            Response::json([
                'success' => true,
                'message' => 'Menu order updated successfully'
            ]);

        } catch (\Exception $e) {
            $this->db->rollBack();
            Response::json([
                'success' => false,
                'message' => 'Error reordering menus: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get menu statistics
     */
    public function statistics() {
        try {
            $sql = "SELECT 
                    COUNT(*) as total_menus,
                    SUM(CASE WHEN parent_id IS NULL THEN 1 ELSE 0 END) as parent_menus,
                    SUM(CASE WHEN parent_id IS NOT NULL THEN 1 ELSE 0 END) as child_menus,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_menus,
                    SUM(CASE WHEN is_visible = 1 THEN 1 ELSE 0 END) as visible_menus
                    FROM menus";
            
            $stmt = $this->db->query($sql);
            $stats = $stmt->fetch(\PDO::FETCH_ASSOC);

            Response::json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'message' => 'Error fetching statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Build hierarchical menu tree
     */
    private function buildMenuTree($menus, $parentId = null) {
        $tree = [];

        foreach ($menus as $menu) {
            if ($menu['parent_id'] == $parentId) {
                $children = $this->buildMenuTree($menus, $menu['id']);
                if (!empty($children)) {
                    $menu['children'] = $children;
                }
                $tree[] = $menu;
            }
        }

        return $tree;
    }

    /**
     * Get next order index for menu
     */
    private function getNextOrderIndex($parentId) {
        $sql = "SELECT COALESCE(MAX(order_index), 0) + 1 as next_index FROM menus WHERE parent_id " . 
               ($parentId ? "= ?" : "IS NULL");
        
        $stmt = $this->db->prepare($sql);
        if ($parentId) {
            $stmt->execute([$parentId]);
        } else {
            $stmt->execute();
        }
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['next_index'];
    }

    /**
     * Validate menu data
     */
    private function validateMenu($data) {
        $errors = [];

        if (empty($data['title'])) {
            $errors['title'] = 'Title is required';
        }

        if (empty($data['url'])) {
            $errors['url'] = 'URL is required';
        }

        if (empty($data['route'])) {
            $errors['route'] = 'Route is required';
        }

        if (isset($data['required_role']) && !in_array($data['required_role'], ['admin', 'user', 'all'])) {
            $errors['required_role'] = 'Invalid role';
        }

        return $errors;
    }
}
