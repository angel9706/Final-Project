<?php
namespace App\Controllers;

use App\Config\Database;
use App\Config\Response;

class UserManagementController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all users with pagination and filters
     */
    public function index() {
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $role = isset($_GET['role']) ? trim($_GET['role']) : '';
            $status = isset($_GET['status']) ? trim($_GET['status']) : '';
            $offset = ($page - 1) * $limit;

            // Build WHERE clause
            $whereConditions = [];
            $params = [];

            if (!empty($search)) {
                $whereConditions[] = "(name LIKE ? OR email LIKE ? OR username LIKE ?)";
                $searchParam = "%{$search}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }

            if (!empty($role) && in_array($role, ['admin', 'user'])) {
                $whereConditions[] = "role = ?";
                $params[] = $role;
            }

            if (!empty($status) && in_array($status, ['active', 'inactive', 'suspended'])) {
                $whereConditions[] = "status = ?";
                $params[] = $status;
            }

            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

            // Count total records
            $countSql = "SELECT COUNT(*) as total FROM users {$whereClause}";
            $countStmt = $this->db->prepare($countSql);
            if (!empty($params)) {
                $countStmt->execute($params);
            } else {
                $countStmt->execute();
            }
            $total = $countStmt->fetch(\PDO::FETCH_ASSOC)['total'];

            // Get users
            $sql = "SELECT id, name, username, email, phone, role, status, avatar, last_login, created_at, updated_at 
                    FROM users {$whereClause} 
                    ORDER BY created_at DESC 
                    LIMIT {$limit} OFFSET {$offset}";
            
            $stmt = $this->db->prepare($sql);
            if (!empty($params)) {
                $stmt->execute($params);
            } else {
                $stmt->execute();
            }
            $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            Response::json([
                'success' => true,
                'data' => [
                    'users' => $users,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => (int)$total,
                        'total_pages' => ceil($total / $limit)
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'message' => 'Error fetching users: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single user by ID
     */
    public function show() {
        try {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

            if ($id <= 0) {
                Response::json([
                    'success' => false,
                    'message' => 'Invalid user ID'
                ], 400);
                return;
            }

            $stmt = $this->db->prepare("SELECT id, name, username, email, phone, role, status, avatar, last_login, created_at, updated_at FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$user) {
                Response::json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
                return;
            }

            Response::json([
                'success' => true,
                'data' => $user
            ]);

        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'message' => 'Error fetching user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new user
     */
    public function store() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // Validation
            $errors = $this->validateUser($data);
            if (!empty($errors)) {
                Response::json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ], 400);
                return;
            }

            // Check if email already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            if ($stmt->fetch()) {
                Response::json([
                    'success' => false,
                    'message' => 'Email already exists'
                ], 400);
                return;
            }

            // Check if username already exists
            if (!empty($data['username'])) {
                $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$data['username']]);
                if ($stmt->fetch()) {
                    Response::json([
                        'success' => false,
                        'message' => 'Username already exists'
                    ], 400);
                    return;
                }
            }

            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

            // Insert user
            $sql = "INSERT INTO users (name, username, email, phone, password, role, status, avatar) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['name'],
                $data['username'] ?? null,
                $data['email'],
                $data['phone'] ?? null,
                $hashedPassword,
                $data['role'] ?? 'user',
                $data['status'] ?? 'active',
                $data['avatar'] ?? null
            ]);

            $userId = $this->db->lastInsertId();

            // Log activity
            $this->logActivity($userId, 'user_created', "User created: {$data['name']}");

            Response::json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => ['id' => $userId]
            ], 201);

        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'message' => 'Error creating user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update existing user
     */
    public function update() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = isset($data['id']) ? (int)$data['id'] : 0;

            if ($id <= 0) {
                Response::json([
                    'success' => false,
                    'message' => 'Invalid user ID'
                ], 400);
                return;
            }

            // Check if user exists
            $stmt = $this->db->prepare("SELECT id, email, username FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $existingUser = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$existingUser) {
                Response::json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
                return;
            }

            // Validation
            $errors = $this->validateUser($data, true);
            if (!empty($errors)) {
                Response::json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ], 400);
                return;
            }

            // Check email uniqueness (exclude current user)
            if ($data['email'] !== $existingUser['email']) {
                $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$data['email'], $id]);
                if ($stmt->fetch()) {
                    Response::json([
                        'success' => false,
                        'message' => 'Email already exists'
                    ], 400);
                    return;
                }
            }

            // Check username uniqueness (exclude current user)
            if (!empty($data['username']) && $data['username'] !== $existingUser['username']) {
                $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $stmt->execute([$data['username'], $id]);
                if ($stmt->fetch()) {
                    Response::json([
                        'success' => false,
                        'message' => 'Username already exists'
                    ], 400);
                    return;
                }
            }

            // Build update query
            $updateFields = [
                'name = ?',
                'username = ?',
                'email = ?',
                'phone = ?',
                'role = ?',
                'status = ?',
                'avatar = ?'
            ];

            $params = [
                $data['name'],
                $data['username'] ?? null,
                $data['email'],
                $data['phone'] ?? null,
                $data['role'] ?? 'user',
                $data['status'] ?? 'active',
                $data['avatar'] ?? null
            ];

            // Update password only if provided
            if (!empty($data['password'])) {
                $updateFields[] = 'password = ?';
                $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
            }

            $params[] = $id;

            $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            // Log activity
            $this->logActivity($id, 'user_updated', "User updated: {$data['name']}");

            Response::json([
                'success' => true,
                'message' => 'User updated successfully'
            ]);

        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'message' => 'Error updating user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete user
     */
    public function destroy() {
        try {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

            if ($id <= 0) {
                Response::json([
                    'success' => false,
                    'message' => 'Invalid user ID'
                ], 400);
                return;
            }

            // Check if user exists
            $stmt = $this->db->prepare("SELECT name FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$user) {
                Response::json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
                return;
            }

            // Delete user
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);

            // Log activity
            $this->logActivity($id, 'user_deleted', "User deleted: {$user['name']}");

            Response::json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);

        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'message' => 'Error deleting user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user statistics
     */
    public function statistics() {
        try {
            $sql = "SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as total_admins,
                    SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as total_users_role,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_users,
                    SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended_users,
                    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as new_today,
                    SUM(CASE WHEN DATE(last_login) = CURDATE() THEN 1 ELSE 0 END) as logged_in_today
                    FROM users";
            
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
     * Validate user data
     */
    private function validateUser($data, $isUpdate = false) {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        }

        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if (!$isUpdate && empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (!empty($data['password']) && strlen($data['password']) < 6) {
            $errors['password'] = 'Password must be at least 6 characters';
        }

        if (isset($data['role']) && !in_array($data['role'], ['admin', 'user'])) {
            $errors['role'] = 'Invalid role';
        }

        if (isset($data['status']) && !in_array($data['status'], ['active', 'inactive', 'suspended'])) {
            $errors['status'] = 'Invalid status';
        }

        return $errors;
    }

    /**
     * Log user activity
     */
    private function logActivity($userId, $action, $description) {
        try {
            $sql = "INSERT INTO user_activity_logs (user_id, action, description, ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $userId,
                $action,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (\Exception $e) {
            // Silently fail if logging fails
        }
    }

    /**
     * Get user menu permissions
     */
    public function getMenuPermissions() {
        try {
            $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
            
            if ($userId === 0) {
                Response::json(['success' => false, 'message' => 'User ID is required'], 400);
                return;
            }
            
            $sql = "SELECT 
                        m.id, m.title, m.route, m.icon,
                        COALESCE(uma.can_access, 0) as can_access
                    FROM menus m
                    LEFT JOIN user_menu_access uma ON m.id = uma.menu_id AND uma.user_id = ?
                    WHERE m.is_active = 1 AND m.parent_id IS NULL
                    ORDER BY m.order_index";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $permissions = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            Response::json(['success' => true, 'data' => $permissions]);
            
        } catch (\Exception $e) {
            Response::json(['success' => false, 'message' => 'Error fetching permissions: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update user menu permissions
     */
    public function updateMenuPermissions() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $userId = $input['user_id'] ?? 0;
            $permissions = $input['permissions'] ?? [];
            
            if ($userId === 0) {
                Response::json(['success' => false, 'message' => 'User ID is required'], 400);
                return;
            }
            
            // Begin transaction
            $this->db->beginTransaction();
            
            // Delete existing permissions
            $stmt = $this->db->prepare("DELETE FROM user_menu_access WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Insert new permissions
            $stmt = $this->db->prepare("
                INSERT INTO user_menu_access (user_id, menu_id, can_access) 
                VALUES (?, ?, ?)
            ");
            
            foreach ($permissions as $menuId => $canAccess) {
                $stmt->execute([$userId, $menuId, $canAccess ? 1 : 0]);
            }
            
            $this->db->commit();
            
            $this->logActivity($userId, 'permissions_updated', 'Menu permissions updated');
            
            Response::json(['success' => true, 'message' => 'Permissions updated successfully']);
            
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            Response::json(['success' => false, 'message' => 'Error updating permissions: ' . $e->getMessage()], 500);
        }
    }
}
