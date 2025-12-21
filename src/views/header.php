<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'SIAPKAK - Sistem Informasi Air Pollution'; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="https://res.cloudinary.com/drgwsncdn/image/upload/v1764506518/logo_kuck1i.png">
    <link rel="shortcut icon" type="image/png" href="https://res.cloudinary.com/drgwsncdn/image/upload/v1764506518/logo_kuck1i.png">
    <link rel="apple-touch-icon" href="https://res.cloudinary.com/drgwsncdn/image/upload/v1764506518/logo_kuck1i.png">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #059669;
            --secondary-color: #0891b2;
            --danger-color: #dc2626;
            --warning-color: #f59e0b;
        }
        
        .spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .badge-info { @apply bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm; }
        .badge-success { @apply bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm; }
        .badge-warning { @apply bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm; }
        .badge-danger { @apply bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-green-600">🌍 SIAPKAK</h1>
                    <span class="ml-2 text-sm text-gray-600">Air Quality Monitoring</span>
                </div>
                
                <!-- Navigation Links -->
                <div class="hidden md:flex space-x-6">
                    <a href="/siapkak/public/" class="text-gray-700 hover:text-green-600 transition">Home</a>
                    <a href="/siapkak/public/dashboard.html" class="text-gray-700 hover:text-green-600 transition">Dashboard</a>
                    <a href="/siapkak/public/analytics.html" class="text-gray-700 hover:text-green-600 transition">Analytics</a>
                    <a href="#" class="text-gray-700 hover:text-green-600 transition">About</a>
                </div>
                
                <!-- User Menu (if logged in) -->
                <div id="userMenu" class="flex items-center space-x-4">
                    <span id="userName" class="text-sm text-gray-600"></span>
                    <button id="logoutBtn" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition hidden">
                        Logout
                    </button>
                    <button id="loginBtn" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">
                        Login
                    </button>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Alert Container -->
    <div id="alertContainer" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4"></div>
    
    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
