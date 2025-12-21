<?php
// Require authentication and admin permission
require_once __DIR__ . '/../vendor/autoload.php';
use App\Config\PermissionHelper;

PermissionHelper::init();
PermissionHelper::requireAdmin();
PermissionHelper::requireAccess('users');

$breadcrumbs = [
    ['label' => 'Manajemen', 'url' => '/siapkak/dashboard'],
    ['label' => 'User Management']
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - SIAPKAK</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="https://res.cloudinary.com/drgwsncdn/image/upload/v1764506518/logo_kuck1i.png">
    <link rel="shortcut icon" type="image/png" href="https://res.cloudinary.com/drgwsncdn/image/upload/v1764506518/logo_kuck1i.png">
    <link rel="apple-touch-icon" href="https://res.cloudinary.com/drgwsncdn/image/upload/v1764506518/logo_kuck1i.png">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        /* Modal styles */
        .modal {
            display: none;
        }
        .modal.active {
            display: flex;
        }
        
        /* Badge styles */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 9999px;
        }
        .badge-admin { background-color: #dbeafe; color: #1e40af; }
        .badge-user { background-color: #e0e7ff; color: #4338ca; }
        .badge-active { background-color: #d1fae5; color: #065f46; }
        .badge-inactive { background-color: #fee2e2; color: #991b1b; }
        .badge-suspended { background-color: #fef3c7; color: #92400e; }
        
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
    </style>
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
                    <img src="img/logo.png" alt="SIAPKAK Logo" class="w-10 h-10 object-contain">
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
                            <p id="userName" class="text-sm font-semibold text-gray-700">User</p>
                            <p id="userEmail" class="text-xs text-gray-500">email@example.com</p>
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
        <?php include __DIR__ . '/../src/views/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 min-h-[calc(100vh-64px)] p-4 sm:p-6 lg:p-8">
            <?php include __DIR__ . '/../src/views/breadcrumb.php'; ?>

            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">User Management</h1>
                <p class="text-gray-600">Kelola pengguna sistem SIAPKAK</p>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Total Users</p>
                            <h3 id="totalUsers" class="text-3xl font-bold text-gray-900 mt-1">0</h3>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Active Users</p>
                            <h3 id="activeUsers" class="text-3xl font-bold text-green-600 mt-1">0</h3>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-user-check text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Admins</p>
                            <h3 id="totalAdmins" class="text-3xl font-bold text-purple-600 mt-1">0</h3>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-user-shield text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">New Today</p>
                            <h3 id="newToday" class="text-3xl font-bold text-orange-600 mt-1">0</h3>
                        </div>
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-user-plus text-orange-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Table Card -->
            <div class="bg-white rounded-xl shadow-sm">
                <!-- Table Header -->
                <div class="p-6 border-b border-gray-200">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex-1">
                            <div class="relative">
                                <input type="text" id="searchInput" placeholder="Cari nama, email, atau username..." 
                                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <select id="roleFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">All Roles</option>
                                <option value="admin">Admin</option>
                                <option value="user">User</option>
                            </select>
                            <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>

                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="hidden lg:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>

                            </tr>
                        </thead>
                        <tbody id="usersTableBody" class="bg-white divide-y divide-gray-200">
                            <!-- Users will be loaded here -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalRecords">0</span> results
                    </div>
                    <div id="pagination" class="flex gap-2">
                        <!-- Pagination buttons will be loaded here -->
                    </div>
                </div>
            </div>
        </main>
    </div>







    <script>
    $(document).ready(function() {
        initAccordion();
        initSidebar();
        loadStatistics();
        loadUsers();

        // Load current user info for header
        $.ajax({
            url: '/siapkak/api/users/me',
            type: 'GET',
            success: function(response) {
                if (response.success && response.data) {
                    $('#userName').text(response.data.name);
                    $('#userEmail').text(response.data.email);
                }
            }
        });

        // Search
        let searchTimeout;
        $('#searchInput').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => loadUsers(), 500);
        });

        // Filters
        $('#roleFilter, #statusFilter').on('change', function() {
            loadUsers();
        });



        // Logout
        $('#logoutBtn').on('click', function() {
            window.location.href = '/siapkak/public/auth/logout.php';
        });
    });

    function initAccordion() {
        $('.accordion-toggle').on('click', function() {
            const content = $(this).next('.accordion-content');
            const icon = $(this).find('.accordion-icon');
            
            // Toggle current
            content.toggleClass('open');
            icon.toggleClass('rotate-180');
        });
    }

    function initSidebar() {
        const sidebar = $('#sidebar');
        const overlay = $('#sidebarOverlay');
        const toggle = $('#sidebarToggle');

        toggle.on('click', function() {
            sidebar.toggleClass('open');
            overlay.toggleClass('hidden');
        });

        overlay.on('click', function() {
            sidebar.removeClass('open');
            overlay.addClass('hidden');
        });

        // Close sidebar when clicking nav link on mobile
        $('.nav-link').on('click', function() {
            if (window.innerWidth < 1024) {
                sidebar.removeClass('open');
                overlay.addClass('hidden');
            }
        });
    }

    function loadStatistics() {
        $.ajax({
            url: '/siapkak/api/users/statistics',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const stats = response.data;
                    $('#totalUsers').text(stats.total_users);
                    $('#activeUsers').text(stats.active_users);
                    $('#totalAdmins').text(stats.total_admins);
                    $('#newToday').text(stats.new_today);
                }
            }
        });
    }

    function loadUsers(page = 1) {
        const search = $('#searchInput').val();
        const role = $('#roleFilter').val();
        const status = $('#statusFilter').val();

        $.ajax({
            url: '/siapkak/api/users',
            type: 'GET',
            data: { page, search, role, status, limit: 10 },
            success: function(response) {
                if (response.success) {
                    renderUsers(response.data.users);
                    renderPagination(response.data.pagination);
                }
            },
            error: function() {
                alert('Error loading users');
            }
        });
    }

    function renderUsers(users) {
        const tbody = $('#usersTableBody');
        tbody.empty();

        if (users.length === 0) {
            tbody.html('<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No users found</td></tr>');
            return;
        }

        users.forEach(user => {
            const roleClass = user.role === 'admin' ? 'badge-admin' : 'badge-user';
            const statusClass = `badge-${user.status}`;
            const lastLogin = user.last_login ? new Date(user.last_login).toLocaleDateString('id-ID') : 'Never';

            tbody.append(`
                <tr class="hover:bg-gray-50">
                    <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-700 rounded-full flex items-center justify-center">
                                <span class="text-white font-bold">${user.name.charAt(0).toUpperCase()}</span>
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900">${user.name}</div>
                                <div class="text-sm text-gray-500">@${user.username || 'N/A'}</div>
                            </div>
                        </div>
                    </td>
                    <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">${user.email}</div>
                        <div class="text-sm text-gray-500">${user.phone || 'N/A'}</div>
                    </td>
                    <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                        <span class="badge ${roleClass}">${user.role}</span>
                    </td>
                    <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                        <span class="badge ${statusClass}">${user.status}</span>
                    </td>
                    <td class="hidden lg:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${lastLogin}
                    </td>

                </tr>
            `);
        });
    }

    function renderPagination(pagination) {
        $('#showingFrom').text((pagination.page - 1) * pagination.limit + 1);
        $('#showingTo').text(Math.min(pagination.page * pagination.limit, pagination.total));
        $('#totalRecords').text(pagination.total);

        const paginationDiv = $('#pagination');
        paginationDiv.empty();

        if (pagination.total_pages <= 1) return;

        // Previous button
        paginationDiv.append(`
            <button onclick="loadUsers(${pagination.page - 1})" 
                ${pagination.page === 1 ? 'disabled' : ''} 
                class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-chevron-left"></i>
            </button>
        `);

        // Page numbers
        for (let i = 1; i <= pagination.total_pages; i++) {
            if (i === 1 || i === pagination.total_pages || (i >= pagination.page - 1 && i <= pagination.page + 1)) {
                paginationDiv.append(`
                    <button onclick="loadUsers(${i})" 
                        class="px-3 py-1 border ${i === pagination.page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 hover:bg-gray-50'} rounded-lg">
                        ${i}
                    </button>
                `);
            } else if (i === pagination.page - 2 || i === pagination.page + 2) {
                paginationDiv.append('<span class="px-2">...</span>');
            }
        }

        // Next button
        paginationDiv.append(`
            <button onclick="loadUsers(${pagination.page + 1})" 
                ${pagination.page === pagination.total_pages ? 'disabled' : ''} 
                class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-chevron-right"></i>
            </button>
        `);
    }

    // Helper functions
    function formatDateTime(dateStr) {
        if (!dateStr) return '-';
        const date = new Date(dateStr);
        return date.toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    </script>
</body>
</html>
