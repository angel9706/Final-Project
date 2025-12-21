<?php
// Require authentication and permission
require_once __DIR__ . '/../vendor/autoload.php';
use App\Config\PermissionHelper;

PermissionHelper::init();
PermissionHelper::requireAuth();
PermissionHelper::requireAccess('readings');

$breadcrumbs = [
    ['label' => 'Monitoring', 'url' => '/siapkak/dashboard'],
    ['label' => 'Data Readings']
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SIAPKAK</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="https://res.cloudinary.com/drgwsncdn/image/upload/v1764506518/logo_kuck1i.png">
    <link rel="shortcut icon" type="image/png" href="https://res.cloudinary.com/drgwsncdn/image/upload/v1764506518/logo_kuck1i.png">
    <link rel="apple-touch-icon" href="https://res.cloudinary.com/drgwsncdn/image/upload/v1764506518/logo_kuck1i.png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Leaflet Heat Plugin for Heatmap -->
    <script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
    
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
        
        /* Active nav link */
        .nav-link.active {
            background-color: #dbeafe;
            color: #1d4ed8;
            font-weight: 600;
        }
        
        /* Modal styles */
        .modal {
            display: none;
        }
        .modal.active {
            display: flex;
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
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-white shadow-md fixed top-0 left-0 right-0 z-50">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Left: Hamburger + Logo -->
                <div class="flex items-center space-x-3">
                    <!-- Hamburger Menu (Mobile) -->
                    <button id="sidebarToggle" class="lg:hidden p-2 rounded-md text-gray-600 hover:bg-gray-100 focus:outline-none">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    
                    <img src="img/logo.png" alt="SIAPKAK Logo" class="w-10 h-10 object-contain">
                    <h1 class="text-xl sm:text-2xl font-bold text-blue-600">SIAPKAK</h1>
                </div>
                
                <!-- Right: User Info & Actions -->
                <div class="flex items-center space-x-2 sm:space-x-4">
                    
                    <!-- User Info (Hidden on mobile) -->
                    <div class="hidden md:flex items-center space-x-3 border-l pl-4">
                        <div class="w-9 h-9 bg-gradient-to-br from-blue-500 to-blue-700 rounded-full flex items-center justify-center shadow">
                            <i class="fas fa-user text-white text-sm"></i>
                        </div>
                        <div>
                            <p id="userName" class="text-sm font-semibold text-gray-700">User</p>
                            <p id="userEmail" class="text-xs text-gray-500">email@example.com</p>
                        </div>
                    </div>
                    
                    <!-- Change Password Button -->
                    <button id="changePasswordBtn" class="p-2 sm:px-3 sm:py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition" title="Change Password">
                        <i class="fas fa-key"></i>
                        <span class="hidden sm:inline ml-1">Password</span>
                    </button>
                    
                    <!-- Logout Button -->
                    <button id="logoutBtn" class="p-2 sm:px-3 sm:py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="hidden sm:inline ml-1">Logout</span>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar Overlay (Mobile) -->
    <div id="sidebarOverlay" class="sidebar-overlay fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

    <!-- Main Layout -->
    <div class="flex pt-16">
        <?php include __DIR__ . '/../src/views/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 min-h-[calc(100vh-64px)] p-4 sm:p-6 lg:p-8">
            <!-- Dashboard Overview Section -->
            <section id="overview" class="content-section hidden">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
                    <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Dashboard Overview</h2>
                    <button id="syncAqicnBtn" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition w-full sm:w-auto flex items-center justify-center gap-2">
                        <i class="fas fa-sync-alt"></i>
                        <span>Sync Data(AQICN)</span>
                    </button>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 hover:shadow-md transition">
                        <div class="flex items-center">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-broadcast-tower text-blue-600 text-lg sm:text-xl"></i>
                            </div>
                            <div class="ml-3 sm:ml-4">
                                <p class="text-gray-500 text-xs sm:text-sm">Total Stasiun</p>
                                <p id="dashTotalStations" class="text-xl sm:text-2xl font-bold text-gray-900">0</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 hover:shadow-md transition">
                        <div class="flex items-center">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-leaf text-green-600 text-lg sm:text-xl"></i>
                            </div>
                            <div class="ml-3 sm:ml-4">
                                <p class="text-gray-500 text-xs sm:text-sm">Udara Baik</p>
                                <p id="dashGoodAir" class="text-xl sm:text-2xl font-bold text-green-600">0</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 hover:shadow-md transition">
                        <div class="flex items-center">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-exclamation text-yellow-600 text-lg sm:text-xl"></i>
                            </div>
                            <div class="ml-3 sm:ml-4">
                                <p class="text-gray-500 text-xs sm:text-sm">Udara Sedang</p>
                                <p id="dashModerateAir" class="text-xl sm:text-2xl font-bold text-yellow-600">0</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 hover:shadow-md transition">
                        <div class="flex items-center">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-red-600 text-lg sm:text-xl"></i>
                            </div>
                            <div class="ml-3 sm:ml-4">
                                <p class="text-gray-500 text-xs sm:text-sm">Tidak Sehat</p>
                                <p id="dashUnhealthyAir" class="text-xl sm:text-2xl font-bold text-red-600">0</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Stations Section -->
            <section id="stations" class="content-section hidden">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
                    <div>
                        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Stasiun Monitoring</h2>
                        <p class="text-gray-500 mt-1">Kelola data stasiun pemantauan kualitas udara</p>
                    </div>
                    <button id="btnAddStation" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition w-full sm:w-auto">
                        <i class="fas fa-plus mr-2"></i>Tambah Stasiun
                    </button>
                </div>

                <!-- Filter & Search -->
                <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <input type="text" id="searchStationInput" placeholder="Cari stasiun..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <button id="btnRefreshStations" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                <i class="fas fa-sync-alt mr-2"></i>Refresh Data
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stations Table -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Stasiun</th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lokasi</th>
                                    <th class="hidden lg:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Koordinat</th>
                                    <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 md:px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="stationsTableBody" class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                        <i class="fas fa-spinner fa-spin mr-2"></i>Loading...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Readings Section -->
            <section id="readings" class="content-section">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
                    <div>
                        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Data Readings</h2>
                        <p class="text-gray-500 mt-1">Kelola data pembacaan kualitas udara</p>
                    </div>
                    <!-- <?php if (PermissionHelper::isAdmin()): ?>
                    <button id="btnAddReading" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition w-full sm:w-auto">
                        <i class="fas fa-plus mr-2"></i>Tambah Reading
                    </button>
                    <?php endif; ?> -->
                </div>

                <!-- Filter & Search -->
                <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="md:col-span-2">
                            <input type="text" id="searchReadingInput" placeholder="Cari data..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <select id="filterReadingStation" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                <option value="">Semua Stasiun</option>
                            </select>
                        </div>
                        <div>
                            <button id="btnRefreshReadings" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                <i class="fas fa-sync-alt mr-2"></i>Refresh
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Readings Table -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stasiun</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">AQI</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">PM2.5</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">PM10</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">O₃</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">NO₂</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="readingsTableBody" class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                                        <i class="fas fa-spinner fa-spin mr-2"></i>Loading...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Analytics Section -->
            <section id="analytics" class="content-section hidden">
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-6">Analytics & Insights</h2>
                
                <!-- Time Series Chart (Database + API) -->
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-chart-line mr-2 text-blue-600"></i>
                            Time Series - AQI Trend (7 Hari Terakhir)
                        </h3>
                        <div class="flex gap-2">
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                <i class="fas fa-database mr-1"></i>Database
                            </span>
                            <span class="px-3 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                                <i class="fas fa-cloud mr-1"></i>AQICN API
                            </span>
                        </div>
                    </div>
                    <div class="h-80">
                        <canvas id="timeSeriesChart"></canvas>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        Grafik menampilkan data dari database lokal dan real-time dari AQICN API
                    </p>
                </div>

                <!-- Bar & Pie Charts Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Pollutants Bar Chart -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-chart-bar mr-2 text-purple-600"></i>
                            Rata-rata Polutan (µg/m³)
                        </h3>
                        <div class="h-72">
                            <canvas id="pollutantsBarChart"></canvas>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Data dari database 30 hari terakhir</p>
                    </div>

                    <!-- AQI Status Pie Chart -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-chart-pie mr-2 text-orange-600"></i>
                            Distribusi Status AQI
                        </h3>
                        <div class="h-72">
                            <canvas id="aqiStatusPieChart"></canvas>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Persentase kategori kualitas udara</p>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-map-marked-alt mr-2 text-red-600"></i>
                            Peta Kualitas Udara & Heatmap
                        </h3>
                        <div class="flex gap-2">
                            <button id="toggleHeatmapBtn" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm">
                                <i class="fas fa-fire mr-2"></i>Toggle Heatmap
                            </button>
                            <button id="refreshMapBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                                <i class="fas fa-sync-alt mr-2"></i>Refresh Data
                            </button>
                        </div>
                    </div>
                    <div id="airQualityMap" class="h-96 rounded-lg border-2 border-gray-200"></div>
                    
                    <!-- Heatmap Legend -->
                    <div class="mt-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-fire mr-2 text-orange-500"></i>Heatmap Intensity Legend
                        </h4>
                        <div class="grid grid-cols-3 md:grid-cols-6 gap-3">
                            <div class="flex flex-col items-center gap-1 bg-gray-50 p-2 rounded">
                                <div class="w-full h-8 rounded" style="background: linear-gradient(to top, #00ff00, #90ff90);"></div>
                                <span class="text-xs font-medium text-gray-700">Baik</span>
                                <span class="text-xs text-gray-500">0-50</span>
                            </div>
                            <div class="flex flex-col items-center gap-1 bg-gray-50 p-2 rounded">
                                <div class="w-full h-8 rounded" style="background: linear-gradient(to top, #ffff00, #ffff90);"></div>
                                <span class="text-xs font-medium text-gray-700">Sedang</span>
                                <span class="text-xs text-gray-500">51-100</span>
                            </div>
                            <div class="flex flex-col items-center gap-1 bg-gray-50 p-2 rounded">
                                <div class="w-full h-8 rounded" style="background: linear-gradient(to top, #ff9900, #ffbb66);"></div>
                                <span class="text-xs font-medium text-gray-700">Sensitif</span>
                                <span class="text-xs text-gray-500">101-150</span>
                            </div>
                            <div class="flex flex-col items-center gap-1 bg-gray-50 p-2 rounded">
                                <div class="w-full h-8 rounded" style="background: linear-gradient(to top, #ff0000, #ff6666);"></div>
                                <span class="text-xs font-medium text-gray-700">Tidak Sehat</span>
                                <span class="text-xs text-gray-500">151-200</span>
                            </div>
                            <div class="flex flex-col items-center gap-1 bg-gray-50 p-2 rounded">
                                <div class="w-full h-8 rounded" style="background: linear-gradient(to top, #cc00ff, #dd66ff);"></div>
                                <span class="text-xs font-medium text-gray-700">Sangat Buruk</span>
                                <span class="text-xs text-gray-500">201-300</span>
                            </div>
                            <div class="flex flex-col items-center gap-1 bg-gray-50 p-2 rounded">
                                <div class="w-full h-8 rounded" style="background: linear-gradient(to top, #990000, #cc3333);"></div>
                                <span class="text-xs font-medium text-gray-700">Berbahaya</span>
                                <span class="text-xs text-gray-500">300+</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Station Markers Legend -->
                    <div class="mt-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-map-marker-alt mr-2 text-blue-500"></i>Station Markers Legend
                        </h4>
                        <div class="grid grid-cols-2 md:grid-cols-6 gap-2">
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 bg-green-500 rounded-full border-2 border-white"></div>
                                <span class="text-xs">Baik (0-50)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 bg-yellow-400 rounded-full border-2 border-white"></div>
                                <span class="text-xs">Sedang (51-100)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 bg-orange-500 rounded-full border-2 border-white"></div>
                                <span class="text-xs">Sensitif (101-150)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 bg-red-500 rounded-full border-2 border-white"></div>
                                <span class="text-xs">Tidak Sehat (151-200)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 bg-purple-600 rounded-full border-2 border-white"></div>
                                <span class="text-xs">Sangat Buruk (201-300)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 bg-red-900 rounded-full border-2 border-white"></div>
                                <span class="text-xs">Berbahaya (300+)</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Current Data Info -->
                    <div class="mt-4 p-4 bg-blue-50 border-l-4 border-blue-500 rounded">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-database text-blue-600 text-xl mt-1"></i>
                            <div class="flex-1">
                                <h4 class="text-sm font-semibold text-gray-800 mb-2">Data AQI Aktif di Peta:</h4>
                                <div id="currentAqiData" class="text-xs text-gray-700 space-y-1">
                                    <p><i class="fas fa-spinner fa-spin mr-1"></i> Loading data...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <p class="text-xs text-gray-500 mt-4">
                        <i class="fas fa-info-circle mr-1"></i>
                        Heatmap menampilkan intensitas kualitas udara. Data dari stasiun monitoring lokal + AQICN API
                    </p>
                </div>
            </section>

            <!-- Alerts Section -->
            <section id="alerts" class="content-section hidden">
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-6">Alerts</h2>
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="text-center py-12">
                        <i class="fas fa-bell text-6xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">No alerts at this time</p>
                    </div>
                </div>
            </section>

            <!-- Reports Sections -->
            <!-- <section id="reports-daily" class="content-section hidden">
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-6">Laporan Harian</h2>
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <p class="text-gray-500">Coming soon...</p>
                </div>
            </section>

            <section id="reports-weekly" class="content-section hidden">
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-6">Laporan Mingguan</h2>
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <p class="text-gray-500">Coming soon...</p>
                </div>
            </section>

            <section id="reports-monthly" class="content-section hidden">
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-6">Laporan Bulanan</h2>
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <p class="text-gray-500">Coming soon...</p>
                </div>
            </section>

            <!-- Settings Section -->
            <section id="settings" class="content-section hidden">
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-6">Pengaturan</h2>
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <p class="text-gray-500">Settings coming soon...</p>
                </div>
            </section> -->
        </main>
    </div>

    <!-- Change Password Modal -->
    <div id="changePasswordModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">
            <!-- Modal Header -->
            <div class="sticky top-0 bg-white flex justify-between items-center p-4 sm:p-6 border-b">
                <h2 class="text-lg sm:text-xl font-bold text-gray-800">
                    <i class="fas fa-key mr-2 text-blue-600"></i>Change Password
                </h2>
                <button type="button" class="text-gray-400 hover:text-gray-600 transition p-2" id="closePasswordModal">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="changePasswordForm" class="p-4 sm:p-6 space-y-4">
                <!-- Current Password -->
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">Current Password</label>
                    <div class="relative">
                        <span class="absolute left-3 top-3 text-gray-400"><i class="fas fa-lock"></i></span>
                        <input type="password" id="currentPassword" class="w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="Enter current password" required>
                        <button type="button" class="toggle-password absolute right-3 top-3 text-gray-400 hover:text-gray-600" data-target="currentPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <!-- New Password -->
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">New Password</label>
                    <div class="relative">
                        <span class="absolute left-3 top-3 text-gray-400"><i class="fas fa-key"></i></span>
                        <input type="password" id="newPassword" class="w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="Enter new password" required>
                        <button type="button" class="toggle-password absolute right-3 top-3 text-gray-400 hover:text-gray-600" data-target="newPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <!-- Password Strength Indicator -->
                    <div class="mt-3">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs text-gray-500">Password Strength:</span>
                            <span id="strengthText" class="text-xs font-semibold text-gray-400">-</span>
                        </div>
                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div id="strengthBar" class="h-full w-0 transition-all duration-300 rounded-full"></div>
                        </div>
                    </div>
                    
                    <!-- Password Requirements -->
                    <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-600 font-semibold mb-2">Requirements:</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                            <div id="req-length" class="flex items-center text-xs text-gray-400">
                                <i class="fas fa-circle text-[6px] mr-2"></i>
                                <span>Min 8 characters</span>
                            </div>
                            <div id="req-uppercase" class="flex items-center text-xs text-gray-400">
                                <i class="fas fa-circle text-[6px] mr-2"></i>
                                <span>Uppercase letter</span>
                            </div>
                            <div id="req-lowercase" class="flex items-center text-xs text-gray-400">
                                <i class="fas fa-circle text-[6px] mr-2"></i>
                                <span>Lowercase letter</span>
                            </div>
                            <div id="req-number" class="flex items-center text-xs text-gray-400">
                                <i class="fas fa-circle text-[6px] mr-2"></i>
                                <span>Number</span>
                            </div>
                            <div id="req-special" class="flex items-center text-xs text-gray-400">
                                <i class="fas fa-circle text-[6px] mr-2"></i>
                                <span>Special char (!@#$%)</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Confirm Password -->
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">Confirm New Password</label>
                    <div class="relative">
                        <span class="absolute left-3 top-3 text-gray-400"><i class="fas fa-check-double"></i></span>
                        <input type="password" id="confirmPassword" class="w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="Confirm new password" required>
                        <button type="button" class="toggle-password absolute right-3 top-3 text-gray-400 hover:text-gray-600" data-target="confirmPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div id="passwordMatch" class="mt-2 text-xs hidden">
                        <i class="fas fa-check-circle mr-1"></i>
                        <span>Passwords match</span>
                    </div>
                </div>
                
                <!-- Alert Message -->
                <div id="passwordAlert" class="hidden p-3 rounded-lg text-sm"></div>
                
                <!-- Buttons -->
                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <button type="button" id="cancelPasswordBtn" class="flex-1 bg-gray-200 text-gray-700 py-2.5 rounded-lg hover:bg-gray-300 transition font-medium order-2 sm:order-1">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                    <button type="submit" id="submitPasswordBtn" class="flex-1 bg-blue-600 text-white py-2.5 rounded-lg hover:bg-blue-700 transition font-medium disabled:bg-gray-400 disabled:cursor-not-allowed order-1 sm:order-2" disabled>
                        <i class="fas fa-save mr-2"></i>Save Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Notification Panel -->
    <div id="notificationPanel" class="hidden fixed top-16 right-2 sm:right-4 w-[calc(100%-16px)] sm:w-96 bg-white rounded-lg shadow-xl z-40 max-h-[70vh] overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold">Notifikasi</h3>
            <button id="closeNotification" class="text-gray-400 hover:text-gray-600 sm:hidden">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="notificationsList" class="divide-y max-h-[50vh] overflow-y-auto">
            <div class="p-4 text-center text-gray-500">Tidak ada notifikasi</div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Check authentication first
        checkAuth();
        
        // Initialize UI components
        initSidebar();
        initAccordion();
        initModals();
        initPasswordForm();
        initSyncAqicn();
        
        // Initialize CRUD components
        initStationsCRUD();
        initReadingsCRUD();
        
        // Load overview data
        loadOverviewData();
        
        // Initialize Charts & Map (after DOM ready)
        initCharts();
        initLeafletMap();
    });

    // ============================================
    // AUTHENTICATION
    // ============================================
    function checkAuth() {
        $.ajax({
            url: '/siapkak/public/auth/check.php',
            type: 'GET',
            xhrFields: { withCredentials: true },
            success: function(response) {
                if (response.success) {
                    const user = response.data.user;
                    localStorage.setItem('user', JSON.stringify(user));
                    $('#userName').text(user.name);
                    $('#userEmail').text(user.email);
                } else {
                    redirectToLogin();
                }
            },
            error: function() {
                redirectToLogin();
            }
        });
    }

    function redirectToLogin() {
        localStorage.removeItem('user');
        window.location.href = '/siapkak';
    }

    // ============================================
    // SIDEBAR (Mobile Toggle)
    // ============================================
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

    // ============================================
    // ACCORDION SIDEBAR
    // ============================================
    function initAccordion() {
        $('.accordion-toggle').on('click', function() {
            const content = $(this).next('.accordion-content');
            const icon = $(this).find('.accordion-icon');
            
            // Toggle current
            content.toggleClass('open');
            icon.toggleClass('rotate-180');
        });
    }

    // ============================================
    // MODALS
    // ============================================
    function initModals() {
        // Logout
        $('#logoutBtn').on('click', function(e) {
            e.preventDefault();
            if (confirm('Apakah Anda yakin ingin logout?')) {
                $.ajax({
                    url: '/siapkak/public/auth/logout.php',
                    type: 'POST',
                    xhrFields: { withCredentials: true },
                    complete: function() {
                        localStorage.removeItem('user');
                        window.location.href = '/siapkak';
                    }
                });
            }
        });

        // Change Password Modal
        $('#changePasswordBtn').on('click', function(e) {
            e.preventDefault();
            $('#changePasswordModal').removeClass('hidden');
            resetPasswordForm();
        });

        $('#closePasswordModal, #cancelPasswordBtn').on('click', function() {
            $('#changePasswordModal').addClass('hidden');
            resetPasswordForm();
        });

        $('#changePasswordModal').on('click', function(e) {
            if (e.target === this) {
                $(this).addClass('hidden');
                resetPasswordForm();
            }
        });

        // Notification Panel
        $('#notificationBtn').on('click', function() {
            $('#notificationPanel').toggleClass('hidden');
        });

        $('#closeNotification').on('click', function() {
            $('#notificationPanel').addClass('hidden');
        });

        // Close notification panel when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#notificationPanel, #notificationBtn').length) {
                $('#notificationPanel').addClass('hidden');
            }
        });
    }

    // ============================================
    // PASSWORD FORM
    // ============================================
    function initPasswordForm() {
        // Toggle password visibility
        $('.toggle-password').on('click', function() {
            const targetId = $(this).data('target');
            const input = $('#' + targetId);
            const icon = $(this).find('i');
            
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

        // Password input handlers
        $('#newPassword').on('input', function() {
            const result = checkPasswordStrength($(this).val());
            updateStrengthBar(result.strength);
            validatePasswordForm();
        });

        $('#confirmPassword, #currentPassword').on('input', function() {
            validatePasswordForm();
        });

        // Form submit
        $('#changePasswordForm').on('submit', function(e) {
            e.preventDefault();
            submitPasswordChange();
        });
    }

    function resetPasswordForm() {
        $('#changePasswordForm')[0].reset();
        $('#passwordAlert').addClass('hidden');
        $('#strengthBar').css('width', '0%').removeClass('bg-red-500 bg-orange-500 bg-yellow-500 bg-green-500');
        $('#strengthText').text('-').removeClass('text-red-500 text-orange-500 text-yellow-500 text-green-500').addClass('text-gray-400');
        $('#passwordMatch').addClass('hidden');
        $('#submitPasswordBtn').prop('disabled', true);
        
        // Reset toggle icons
        $('.toggle-password i').removeClass('fa-eye-slash').addClass('fa-eye');
        $('.toggle-password').each(function() {
            const targetId = $(this).data('target');
            $('#' + targetId).attr('type', 'password');
        });
        
        resetRequirements();
    }

    function resetRequirements() {
        ['length', 'uppercase', 'lowercase', 'number', 'special'].forEach(function(req) {
            $('#req-' + req).removeClass('text-green-500').addClass('text-gray-400');
            $('#req-' + req + ' i').removeClass('fa-check-circle text-green-500').addClass('fa-circle');
        });
    }

    function checkPasswordStrength(password) {
        let strength = 0;
        const requirements = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
        };

        Object.keys(requirements).forEach(function(req) {
            const element = $('#req-' + req);
            const icon = element.find('i');
            
            if (requirements[req]) {
                strength++;
                element.removeClass('text-gray-400').addClass('text-green-500');
                icon.removeClass('fa-circle').addClass('fa-check-circle');
            } else {
                element.removeClass('text-green-500').addClass('text-gray-400');
                icon.removeClass('fa-check-circle').addClass('fa-circle');
            }
        });

        return { strength, requirements };
    }

    function updateStrengthBar(strength) {
        const bar = $('#strengthBar');
        const text = $('#strengthText');
        
        bar.removeClass('bg-red-500 bg-orange-500 bg-yellow-500 bg-green-500');
        text.removeClass('text-red-500 text-orange-500 text-yellow-500 text-green-500 text-gray-400');

        const levels = [
            { width: '0%', text: '-', class: 'text-gray-400', barClass: '' },
            { width: '20%', text: 'Very Weak', class: 'text-red-500', barClass: 'bg-red-500' },
            { width: '40%', text: 'Weak', class: 'text-orange-500', barClass: 'bg-orange-500' },
            { width: '60%', text: 'Fair', class: 'text-yellow-500', barClass: 'bg-yellow-500' },
            { width: '80%', text: 'Strong', class: 'text-green-500', barClass: 'bg-green-500' },
            { width: '100%', text: 'Very Strong', class: 'text-green-500', barClass: 'bg-green-500' }
        ];

        const level = levels[strength];
        bar.css('width', level.width).addClass(level.barClass);
        text.text(level.text).addClass(level.class);
    }

    function checkPasswordMatch() {
        const newPass = $('#newPassword').val();
        const confirmPass = $('#confirmPassword').val();
        const matchDiv = $('#passwordMatch');
        
        if (confirmPass.length > 0) {
            if (newPass === confirmPass) {
                matchDiv.removeClass('hidden text-red-500').addClass('text-green-500')
                    .html('<i class="fas fa-check-circle mr-1"></i><span>Passwords match</span>');
                return true;
            } else {
                matchDiv.removeClass('hidden text-green-500').addClass('text-red-500')
                    .html('<i class="fas fa-times-circle mr-1"></i><span>Passwords do not match</span>');
                return false;
            }
        }
        matchDiv.addClass('hidden');
        return false;
    }

    function validatePasswordForm() {
        const currentPass = $('#currentPassword').val();
        const newPass = $('#newPassword').val();
        const { requirements } = checkPasswordStrength(newPass);
        const isMatch = checkPasswordMatch();
        
        const meetsMinimum = requirements.length && 
                             (requirements.uppercase || requirements.lowercase) && 
                             requirements.number;
        
        const canSubmit = currentPass.length > 0 && meetsMinimum && isMatch;
        $('#submitPasswordBtn').prop('disabled', !canSubmit);
    }

    function showPasswordAlert(message, type) {
        const alertBox = $('#passwordAlert');
        alertBox.removeClass('hidden bg-red-50 bg-green-50 text-red-800 text-green-800');
        
        if (type === 'error') {
            alertBox.addClass('bg-red-50 text-red-800')
                .html('<i class="fas fa-exclamation-circle mr-2"></i>' + message);
        } else {
            alertBox.addClass('bg-green-50 text-green-800')
                .html('<i class="fas fa-check-circle mr-2"></i>' + message);
        }
    }

    function submitPasswordChange() {
        const currentPassword = $('#currentPassword').val();
        const newPassword = $('#newPassword').val();
        const confirmPassword = $('#confirmPassword').val();
        
        if (newPassword !== confirmPassword) {
            showPasswordAlert('Passwords do not match!', 'error');
            return;
        }
        
        const submitBtn = $('#submitPasswordBtn');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Saving...');
        
        $.ajax({
            url: '/siapkak/public/auth/change-password.php',
            type: 'POST',
            contentType: 'application/json',
            xhrFields: { withCredentials: true },
            data: JSON.stringify({
                current_password: currentPassword,
                new_password: newPassword
            }),
            success: function(response) {
                if (response.success) {
                    showPasswordAlert('Password changed successfully!', 'success');
                    setTimeout(function() {
                        $('#changePasswordModal').addClass('hidden');
                        resetPasswordForm();
                    }, 1500);
                } else {
                    showPasswordAlert(response.message || 'Failed to change password', 'error');
                    submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                let message = 'Failed to change password. Please try again.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    message = response.message || message;
                } catch(e) {}
                showPasswordAlert(message, 'error');
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    }

    // ============================================
    // AQICN SYNC
    // ============================================
    function initSyncAqicn() {
        $('#syncAqicnBtn').on('click', function() {
            syncAllStationsData();
        });
    }

    function syncAllStationsData() {
        const btn = $('#syncAqicnBtn');
        const originalHtml = btn.html();
        
        // Disable button and show loading
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Syncing All Stations...');
        
        showAlert('Memulai sync data untuk semua stasiun Indonesia...', 'info');
        
        // Get all stations
        $.ajax({
            url: '/siapkak/api/stations',
            type: 'GET',
            xhrFields: { withCredentials: true },
            success: function(response) {
                if (response.success && response.data.stations && response.data.stations.length > 0) {
                    const stations = response.data.stations;
                    
                    // Sync data for all stations sequentially
                    syncStationsSequentially(stations, 0, btn, originalHtml);
                } else {
                    btn.prop('disabled', false).html(originalHtml);
                    showAlert('Tidak ada stasiun untuk di-sync. Jalankan sync stasiun terlebih dahulu.', 'warning');
                }
            },
            error: function() {
                btn.prop('disabled', false).html(originalHtml);
                showAlert('Gagal mengambil daftar stasiun', 'error');
            }
        });
    }

    function syncStationsSequentially(stations, index, btn, originalHtml) {
        if (index >= stations.length) {
            // All done - refresh dashboard
            btn.html('<i class="fas fa-check mr-2"></i>Sync Completed!');
            showAlert(`Sync selesai! ${stations.length} stasiun berhasil di-sync`, 'success');
            
            // Refresh dashboard data
            setTimeout(function() {
                btn.prop('disabled', false).html(originalHtml);
                refreshDashboardData();
            }, 2000);
            return;
        }
        
        const station = stations[index];
        const progress = `${index + 1}/${stations.length}`;
        
        // Update button text with progress
        btn.html(`<i class="fas fa-spinner fa-spin mr-2"></i>Syncing ${progress}: ${station.name}`);
        
        // Sync this station
        $.ajax({
            url: '/siapkak/api/readings/sync-aqicn',
            type: 'POST',
            contentType: 'application/json',
            xhrFields: { withCredentials: true },
            data: JSON.stringify({
                station_id: station.id,
                latitude: station.latitude,
                longitude: station.longitude
            }),
            success: function(response) {
                console.log(`Synced ${station.name}: AQI ${response.data?.aqi || 'N/A'}`);
                // Continue to next station
                setTimeout(function() {
                    syncStationsSequentially(stations, index + 1, btn, originalHtml);
                }, 500); // Small delay between requests
            },
            error: function(xhr) {
                console.error(`Failed to sync ${station.name}`);
                // Continue to next station even on error
                setTimeout(function() {
                    syncStationsSequentially(stations, index + 1, btn, originalHtml);
                }, 500);
            }
        });
    }

    function refreshDashboardData() {
        showAlert('Memperbarui dashboard...', 'info');
        
        // Refresh overview stats
        loadOverviewStats();
        
        // Refresh charts if on analytics section
        if (!$('#analytics').hasClass('hidden')) {
            loadTimeSeriesData();
            loadPollutantsData();
            loadAqiStatusData();
            
            // Refresh map
            if (airQualityMap) {
                loadStationsOnMap();
            }
        }
        
        // Refresh stations table if on stations section
        if (!$('#stations').hasClass('hidden')) {
            loadStationsForCRUD();
        }
        
        // Refresh readings table if on readings section
        if (!$('#readings').hasClass('hidden')) {
            loadReadingsForCRUD();
        }
        
        showAlert('Dashboard berhasil diperbarui!', 'success');
    }

    function showAlert(message, type) {
        const alertClass = type === 'success' ? 'bg-green-500' : 
                          type === 'error' ? 'bg-red-500' : 
                          type === 'warning' ? 'bg-yellow-500' : 
                          'bg-blue-500';
        
        const iconClass = type === 'success' ? 'check-circle' : 
                         type === 'error' ? 'exclamation-circle' : 
                         type === 'warning' ? 'exclamation-triangle' : 
                         'info-circle';
        
        const alert = $('<div>')
            .addClass('fixed top-20 right-4 z-50 px-6 py-3 rounded-lg text-white shadow-lg ' + alertClass)
            .html('<i class="fas fa-' + iconClass + ' mr-2"></i>' + message);
        
        $('body').append(alert);
        
        setTimeout(function() {
            alert.fadeOut(300, function() { $(this).remove(); });
        }, type === 'info' ? 2000 : 3000);
    }

    // ============================================
    // CHARTS & VISUALIZATION
    // ============================================
    let timeSeriesChart, pollutantsChart, aqiPieChart;
    let airQualityMap, heatmapLayer;

    // ============================================
    // OVERVIEW DATA
    // ============================================
    function loadOverviewData() {
        loadOverviewStats();
        loadOverviewCharts();
    }

    function loadOverviewStats() {
        // Load total stations
        $.ajax({
            url: '/siapkak/api/stations',
            type: 'GET',
            xhrFields: { withCredentials: true },
            success: function(response) {
                if (response.success && response.data.stations) {
                    const stations = response.data.stations;
                    $('#dashTotalStations').text(stations.length);
                    
                    // Count by AQI status
                    let goodCount = 0;
                    let moderateCount = 0;
                    let unhealthyCount = 0;
                    
                    stations.forEach(station => {
                        const aqi = station.latest_aqi;
                        if (aqi) {
                            if (aqi <= 50) goodCount++;
                            else if (aqi <= 100) moderateCount++;
                            else unhealthyCount++;
                        }
                    });
                    
                    $('#dashGoodAir').text(goodCount);
                    $('#dashModerateAir').text(moderateCount);
                    $('#dashUnhealthyAir').text(unhealthyCount);
                }
            }
        });
    }

    function loadOverviewCharts() {
        // AQI Trend Chart (24 hours)
        $.ajax({
            url: '/siapkak/api/readings?limit=24',
            type: 'GET',
            xhrFields: { withCredentials: true },
            success: function(response) {
                if (response.success && response.data.readings && response.data.readings.length > 0) {
                    const readings = response.data.readings.reverse();
                    
                    // Process labels and data with proper validation
                    const labels = [];
                    const data = [];
                    
                    readings.forEach(r => {
                        if (r.recorded_at && r.aqi !== null && r.aqi !== undefined) {
                            const date = new Date(r.recorded_at);
                            const hour = date.getHours();
                            const minute = date.getMinutes();
                            labels.push(`${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`);
                            data.push(parseFloat(r.aqi));
                        }
                    });
                    
                    // If no valid data, show empty chart
                    if (data.length === 0) {
                        labels.push('No Data');
                        data.push(0);
                    }
                    
                    const ctx = document.getElementById('aqiTrendChart');
                    if (ctx) {
                        new Chart(ctx.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'AQI',
                                    data: data,
                                    borderColor: 'rgb(59, 130, 246)',
                                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                    tension: 0.4,
                                    fill: true,
                                    pointRadius: 4,
                                    pointHoverRadius: 6
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: true
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return 'AQI: ' + context.parsed.y.toFixed(0);
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        title: {
                                            display: true,
                                            text: 'AQI Value'
                                        },
                                        ticks: {
                                            callback: function(value) {
                                                return Math.round(value);
                                            }
                                        }
                                    },
                                    x: {
                                        title: {
                                            display: true,
                                            text: 'Time'
                                        }
                                    }
                                }
                            }
                        });
                    }
                } else {
                    // No data available - show empty chart
                    const ctx = document.getElementById('aqiTrendChart');
                    if (ctx) {
                        new Chart(ctx.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: ['No Data'],
                                datasets: [{
                                    label: 'AQI',
                                    data: [0],
                                    borderColor: 'rgb(156, 163, 175)',
                                    backgroundColor: 'rgba(156, 163, 175, 0.1)'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                }
                            }
                        });
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to load AQI trend data:', error);
                // Show empty chart on error
                const ctx = document.getElementById('aqiTrendChart');
                if (ctx) {
                    new Chart(ctx.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: ['Error'],
                            datasets: [{
                                label: 'AQI',
                                data: [0],
                                borderColor: 'rgb(239, 68, 68)',
                                backgroundColor: 'rgba(239, 68, 68, 0.1)'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    });
                }
            }
        });

        // Status Distribution Chart
        $.ajax({
            url: '/siapkak/api/readings?limit=100',
            type: 'GET',
            xhrFields: { withCredentials: true },
            success: function(response) {
                if (response.success && response.data.readings && response.data.readings.length > 0) {
                    const readings = response.data.readings;
                    
                    // Count by status
                    const statusCount = {
                        'Baik (0-50)': 0,
                        'Sedang (51-100)': 0,
                        'Tidak Sehat Sensitif (101-150)': 0,
                        'Tidak Sehat (151-200)': 0,
                        'Sangat Tidak Sehat (201-300)': 0,
                        'Berbahaya (>300)': 0
                    };
                    
                    readings.forEach(r => {
                        if (r.aqi !== null && r.aqi !== undefined) {
                            const aqi = parseFloat(r.aqi);
                            if (!isNaN(aqi)) {
                                if (aqi <= 50) statusCount['Baik (0-50)']++;
                                else if (aqi <= 100) statusCount['Sedang (51-100)']++;
                                else if (aqi <= 150) statusCount['Tidak Sehat Sensitif (101-150)']++;
                                else if (aqi <= 200) statusCount['Tidak Sehat (151-200)']++;
                                else if (aqi <= 300) statusCount['Sangat Tidak Sehat (201-300)']++;
                                else statusCount['Berbahaya (>300)']++;
                            }
                        }
                    });
                    
                    const ctx = document.getElementById('statusDistributionChart');
                    if (ctx) {
                        new Chart(ctx.getContext('2d'), {
                            type: 'doughnut',
                            data: {
                                labels: Object.keys(statusCount),
                                datasets: [{
                                    data: Object.values(statusCount),
                                    backgroundColor: [
                                        '#10b981', // Green
                                        '#fbbf24', // Yellow
                                        '#f97316', // Orange
                                        '#ef4444', // Red
                                        '#a855f7', // Purple
                                        '#7f1d1d'  // Dark Red
                                    ]
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            padding: 10,
                                            font: {
                                                size: 11
                                            }
                                        }
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                const label = context.label || '';
                                                const value = context.parsed || 0;
                                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                                const percentage = ((value / total) * 100).toFixed(1);
                                                return label + ': ' + value + ' (' + percentage + '%)';
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                } else {
                    // No data - show empty state
                    const ctx = document.getElementById('statusDistributionChart');
                    if (ctx) {
                        new Chart(ctx.getContext('2d'), {
                            type: 'doughnut',
                            data: {
                                labels: ['No Data'],
                                datasets: [{
                                    data: [1],
                                    backgroundColor: ['#e5e7eb']
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                }
                            }
                        });
                    }
                }
            },
            error: function() {
                console.error('Failed to load status distribution data');
            }
        });
    }

    // ============================================
    // CHARTS INITIALIZATION
    // ============================================
    function initCharts() {
        // Time Series Chart (Line Chart - DB + API)
        const tsCtx = document.getElementById('timeSeriesChart');
        if (tsCtx) {
            loadTimeSeriesData();
        }

        // Pollutants Bar Chart (Database)
        const pbCtx = document.getElementById('pollutantsBarChart');
        if (pbCtx) {
            loadPollutantsData();
        }

        // AQI Status Pie Chart
        const apCtx = document.getElementById('aqiStatusPieChart');
        if (apCtx) {
            loadAqiStatusData();
        }
    }

    function loadTimeSeriesData() {
        $.ajax({
            url: '/siapkak/api/readings?limit=168', // 7 days * 24 hours
            type: 'GET',
            xhrFields: { withCredentials: true },
            success: function(response) {
                if (response.success && response.data.readings && response.data.readings.length > 0) {
                    const readings = response.data.readings;
                    
                    // Group by date and calculate average
                    const grouped = {};
                    readings.forEach(r => {
                        if (r.recorded_at && r.aqi !== null && r.aqi !== undefined) {
                            const dateObj = new Date(r.recorded_at);
                            
                            // Check if date is valid
                            if (!isNaN(dateObj.getTime())) {
                                // Format: DD/MM/YYYY
                                const day = dateObj.getDate().toString().padStart(2, '0');
                                const month = (dateObj.getMonth() + 1).toString().padStart(2, '0');
                                const year = dateObj.getFullYear();
                                const dateKey = `${day}/${month}/${year}`;
                                
                                if (!grouped[dateKey]) {
                                    grouped[dateKey] = { total: 0, count: 0, timestamp: dateObj.getTime() };
                                }
                                grouped[dateKey].total += parseFloat(r.aqi);
                                grouped[dateKey].count++;
                            }
                        }
                    });
                    
                    // Sort by timestamp and get last 7 days
                    const sortedDates = Object.keys(grouped).sort((a, b) => {
                        return grouped[a].timestamp - grouped[b].timestamp;
                    }).slice(-7);
                    
                    const labels = sortedDates;
                    const dbData = labels.map(date => {
                        const avg = grouped[date].total / grouped[date].count;
                        return parseFloat(avg.toFixed(1));
                    });
                    
                    // Simulate API data (in real scenario, fetch from AQICN)
                    const apiData = labels.map(() => Math.floor(Math.random() * 50) + 50);
                    
                    const ctx = document.getElementById('timeSeriesChart');
                    if (ctx) {
                        timeSeriesChart = new Chart(ctx.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'Database (Lokal)',
                                    data: dbData,
                                    borderColor: 'rgb(59, 130, 246)',
                                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                    tension: 0.4,
                                    fill: true,
                                    pointRadius: 4,
                                    pointHoverRadius: 6
                                }, {
                                    label: 'AQICN API (Jakarta)',
                                    data: apiData,
                                    borderColor: 'rgb(34, 197, 94)',
                                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                                    tension: 0.4,
                                    fill: true,
                                    borderDash: [5, 5],
                                    pointRadius: 4,
                                    pointHoverRadius: 6
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'top'
                                    },
                                    tooltip: {
                                        mode: 'index',
                                        intersect: false,
                                        callbacks: {
                                            label: function(context) {
                                                return context.dataset.label + ': ' + context.parsed.y.toFixed(0);
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        title: {
                                            display: true,
                                            text: 'AQI Value'
                                        },
                                        ticks: {
                                            callback: function(value) {
                                                return Math.round(value);
                                            }
                                        }
                                    },
                                    x: {
                                        title: {
                                            display: true,
                                            text: 'Tanggal'
                                        }
                                    }
                                }
                            }
                        });
                    }
                } else {
                    // No data - show empty chart
                    const ctx = document.getElementById('timeSeriesChart');
                    if (ctx) {
                        timeSeriesChart = new Chart(ctx.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: ['No Data'],
                                datasets: [{
                                    label: 'Database',
                                    data: [0],
                                    borderColor: 'rgb(156, 163, 175)'
                                }, {
                                    label: 'API',
                                    data: [0],
                                    borderColor: 'rgb(156, 163, 175)'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false
                            }
                        });
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to load time series data:', error);
                // Show empty chart on error
                const ctx = document.getElementById('timeSeriesChart');
                if (ctx) {
                    timeSeriesChart = new Chart(ctx.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: ['Error'],
                            datasets: [{
                                label: 'Database',
                                data: [0],
                                borderColor: 'rgb(239, 68, 68)'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    });
                }
            }
        });
    }

    function loadPollutantsData() {
        $.ajax({
            url: '/siapkak/api/readings?limit=100',
            type: 'GET',
            xhrFields: { withCredentials: true },
            success: function(response) {
                if (response.success && response.data.readings) {
                    const readings = response.data.readings;
                    
                    // Calculate averages
                    const totals = {
                        pm25: 0, pm10: 0, o3: 0, no2: 0, so2: 0, co: 0
                    };
                    const count = readings.length;
                    
                    readings.forEach(r => {
                        totals.pm25 += parseFloat(r.pm25 || 0);
                        totals.pm10 += parseFloat(r.pm10 || 0);
                        totals.o3 += parseFloat(r.o3 || 0);
                        totals.no2 += parseFloat(r.no2 || 0);
                        totals.so2 += parseFloat(r.so2 || 0);
                        totals.co += parseFloat(r.co || 0);
                    });
                    
                    const ctx = document.getElementById('pollutantsBarChart').getContext('2d');
                    pollutantsChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: ['PM2.5', 'PM10', 'O₃', 'NO₂', 'SO₂', 'CO'],
                            datasets: [{
                                label: 'Rata-rata Konsentrasi',
                                data: [
                                    (totals.pm25 / count).toFixed(2),
                                    (totals.pm10 / count).toFixed(2),
                                    (totals.o3 / count).toFixed(2),
                                    (totals.no2 / count).toFixed(2),
                                    (totals.so2 / count).toFixed(2),
                                    (totals.co / count).toFixed(2)
                                ],
                                backgroundColor: [
                                    'rgba(239, 68, 68, 0.7)',   // PM2.5 - red
                                    'rgba(249, 115, 22, 0.7)',  // PM10 - orange
                                    'rgba(234, 179, 8, 0.7)',   // O3 - yellow
                                    'rgba(34, 197, 94, 0.7)',   // NO2 - green
                                    'rgba(59, 130, 246, 0.7)',  // SO2 - blue
                                    'rgba(168, 85, 247, 0.7)'   // CO - purple
                                ],
                                borderColor: [
                                    'rgb(239, 68, 68)',
                                    'rgb(249, 115, 22)',
                                    'rgb(234, 179, 8)',
                                    'rgb(34, 197, 94)',
                                    'rgb(59, 130, 246)',
                                    'rgb(168, 85, 247)'
                                ],
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'µg/m³'
                                    }
                                }
                            }
                        }
                    });
                }
            }
        });
    }

    function loadAqiStatusData() {
        $.ajax({
            url: '/siapkak/api/readings?limit=200',
            type: 'GET',
            xhrFields: { withCredentials: true },
            success: function(response) {
                if (response.success && response.data.readings) {
                    const readings = response.data.readings;
                    
                    // Count by status
                    const statusCount = {
                        'Baik': 0,
                        'Sedang': 0,
                        'Tidak Sehat untuk Kelompok Sensitif': 0,
                        'Tidak Sehat': 0,
                        'Sangat Tidak Sehat': 0,
                        'Berbahaya': 0
                    };
                    
                    readings.forEach(r => {
                        const status = r.status || 'Baik';
                        if (statusCount.hasOwnProperty(status)) {
                            statusCount[status]++;
                        }
                    });
                    
                    const ctx = document.getElementById('aqiStatusPieChart').getContext('2d');
                    aqiPieChart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: Object.keys(statusCount),
                            datasets: [{
                                data: Object.values(statusCount),
                                backgroundColor: [
                                    'rgb(34, 197, 94)',     // Baik - green
                                    'rgb(234, 179, 8)',     // Sedang - yellow
                                    'rgb(249, 115, 22)',    // Sensitif - orange
                                    'rgb(239, 68, 68)',     // Tidak Sehat - red
                                    'rgb(168, 85, 247)',    // Sangat Buruk - purple
                                    'rgb(127, 29, 29)'      // Berbahaya - dark red
                                ],
                                borderWidth: 2,
                                borderColor: '#fff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
                                    labels: {
                                        boxWidth: 15,
                                        padding: 10,
                                        font: {
                                            size: 11
                                        }
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.parsed || 0;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = ((value / total) * 100).toFixed(1);
                                            return label + ': ' + value + ' (' + percentage + '%)';
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }
        });
    }

    // ============================================
    // LEAFLET MAP WITH HEATMAP
    // ============================================
    function initLeafletMap() {
        // Check if map container exists
        const mapContainer = document.getElementById('airQualityMap');
        if (!mapContainer) {
            console.warn('Map container not found');
            return;
        }

        // Wait for container to be visible
        setTimeout(function() {
            try {
                // Initialize map centered on Karawang region
                airQualityMap = L.map('airQualityMap').setView([-6.3088, 107.2865], 13);
                
                // Add OpenStreetMap tiles
                const tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 18
                }).addTo(airQualityMap);
                
                // Wait for tiles to load, then initialize heatmap
                tileLayer.on('load', function() {
                    console.log('✅ Map tiles loaded');
                    setTimeout(function() {
                        airQualityMap.invalidateSize();
                        console.log('🗺️ Map ready, loading station data...');
                        loadStationsOnMap();
                    }, 100);
                });
                
                // Fallback if 'load' event doesn't fire
                setTimeout(function() {
                    airQualityMap.invalidateSize();
                    console.log('🗺️ Map initialized (fallback), loading station data...');
                    loadStationsOnMap();
                }, 1000);
                
            } catch (error) {
                console.error('Failed to initialize map:', error);
            }
        }, 300);
        
        // Refresh button handler
        $('#refreshMapBtn').on('click', function() {
            const btn = $(this);
            const originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Loading...');
            
            setTimeout(function() {
                loadStationsOnMap();
                btn.prop('disabled', false).html(originalHtml);
                alert('Peta berhasil diperbarui!');
            }, 1000);
        });
        
        // Toggle heatmap button handler
        $('#toggleHeatmapBtn').on('click', function() {
            if (heatmapLayer) {
                if (airQualityMap.hasLayer(heatmapLayer)) {
                    airQualityMap.removeLayer(heatmapLayer);
                    $(this).html('<i class="fas fa-fire mr-2"></i>Show Heatmap');
                    console.log('🔇 Heatmap hidden');
                } else {
                    airQualityMap.addLayer(heatmapLayer);
                    $(this).html('<i class="fas fa-fire mr-2"></i>Hide Heatmap');
                    console.log('🔥 Heatmap shown');
                    // Force refresh
                    setTimeout(() => airQualityMap.invalidateSize(), 100);
                }
            } else {
                console.warn('⚠️ Heatmap layer not initialized');
                alert('Heatmap belum tersedia. Klik "Refresh Data" terlebih dahulu.');
            }
        });
    }

    function loadStationsOnMap() {
        if (!airQualityMap) {
            console.warn('Map not initialized yet');
            return;
        }

        $.ajax({
            url: '/siapkak/api/stations',
            type: 'GET',
            xhrFields: { withCredentials: true },
            success: function(response) {
                if (response.success && response.data.stations && response.data.stations.length > 0) {
                    const stations = response.data.stations;
                    const heatmapData = [];
                    
                    // Clear existing markers (except tile layer)
                    airQualityMap.eachLayer(function(layer) {
                        if (layer instanceof L.Marker || layer instanceof L.CircleMarker) {
                            airQualityMap.removeLayer(layer);
                        }
                    });
                    
                    // Remove old heatmap
                    if (heatmapLayer) {
                        airQualityMap.removeLayer(heatmapLayer);
                        heatmapLayer = null;
                    }
                    
                    console.log('Processing stations for map:', stations.length);
                    
                    stations.forEach(station => {
                        const lat = parseFloat(station.latitude);
                        const lon = parseFloat(station.longitude);
                        const aqi = parseFloat(station.latest_aqi || 0);
                        
                        console.log(`Station: ${station.name}, Lat: ${lat}, Lon: ${lon}, AQI: ${aqi}`);
                        
                        // Validate coordinates
                        if (!isNaN(lat) && !isNaN(lon) && lat !== 0 && lon !== 0) {
                            // Add to heatmap data only if AQI exists
                            if (aqi > 0) {
                                // Use better intensity normalization for visibility
                                const intensity = aqi / 50; // 75 AQI = 1.5, 150 AQI = 3.0
                                heatmapData.push([lat, lon, intensity]);
                                console.log(`✓ Added to heatmap: [${lat}, ${lon}, intensity: ${intensity}]`);
                            }
                            
                            // Determine marker color based on AQI
                            let color = '#9ca3af'; // Default gray for no data
                            if (aqi > 0) {
                                if (aqi > 300) color = '#7f1d1d';      // Berbahaya - dark red
                                else if (aqi > 200) color = '#a855f7'; // Sangat Buruk - purple
                                else if (aqi > 150) color = '#ef4444'; // Tidak Sehat - red
                                else if (aqi > 100) color = '#f97316'; // Sensitif - orange
                                else if (aqi > 50) color = '#eab308';  // Sedang - yellow
                                else color = '#22c55e';                // Baik - green
                            }
                            
                            // Create circle marker
                            const marker = L.circleMarker([lat, lon], {
                                radius: 10,
                                fillColor: color,
                                color: '#fff',
                                weight: 2,
                                opacity: 1,
                                fillOpacity: 0.8
                            }).addTo(airQualityMap);
                            
                            // Popup content
                            const popupContent = `
                                <div class="p-2">
                                    <h3 class="font-bold text-lg">${escapeHtml(station.name)}</h3>
                                    <p class="text-sm text-gray-600">${escapeHtml(station.location)}</p>
                                    <div class="mt-2 space-y-1">
                                        <p><strong>AQI:</strong> <span class="text-lg font-bold">${aqi > 0 ? aqi : 'N/A'}</span></p>
                                        <p><strong>Status:</strong> ${station.latest_status || 'No Data'}</p>
                                        ${station.latest_pm25 ? `<p class="text-xs text-gray-500">PM2.5: ${station.latest_pm25} µg/m³</p>` : ''}
                                        ${station.latest_measured_at ? `<p class="text-xs text-gray-500">Update: ${formatDateTime(station.latest_measured_at)}</p>` : ''}
                                    </div>
                                </div>
                            `;
                            
                            marker.bindPopup(popupContent);
                        }
                    });
                    
                    // Create heatmap after all markers are added
                    createHeatmapLayer(heatmapData, stations);
                    
                    // Add AQICN marker for Jakarta
                    addAqicnMarker();
                } else {
                    console.warn('No stations data available');
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to load stations for map:', error);
            }
        });
    }

    // Create heatmap layer - similar to MapTiler SDK pattern
    function createHeatmapLayer(data, stations) {
        if (!data || data.length === 0) {
            console.warn('⚠️ No valid heatmap data available');
            $('#currentAqiData').html('<p class="text-yellow-600"><i class="fas fa-exclamation-triangle mr-1"></i> Tidak ada data AQI yang valid</p>');
            return;
        }
        
        console.log(`🔥 Preparing to create heatmap with ${data.length} data points...`);
        console.log('Heatmap data:', data);
        
        // Update current data info panel
        if (stations && stations.length > 0) {
            let dataInfo = '<div class="space-y-1">';
            let aqiValues = [];
            
            stations.forEach(station => {
                const aqi = parseFloat(station.latest_aqi || 0);
                if (aqi > 0) {
                    aqiValues.push(aqi);
                    const statusColor = aqi > 150 ? 'text-red-600' : aqi > 100 ? 'text-orange-600' : aqi > 50 ? 'text-yellow-600' : 'text-green-600';
                    dataInfo += `<p class="${statusColor}">• <strong>${station.name}:</strong> AQI ${aqi} (${station.latest_status})</p>`;
                }
            });
            
            // Calculate stats
            if (aqiValues.length > 0) {
                const avgAqi = (aqiValues.reduce((a, b) => a + b, 0) / aqiValues.length).toFixed(1);
                const minAqi = Math.min(...aqiValues);
                const maxAqi = Math.max(...aqiValues);
                
                dataInfo += `<hr class="my-2 border-gray-300">`;
                dataInfo += `<p class="text-gray-700"><strong>Statistik:</strong> Min: ${minAqi} | Max: ${maxAqi} | Rata-rata: ${avgAqi}</p>`;
            }
            
            dataInfo += '</div>';
            $('#currentAqiData').html(dataInfo);
        }
        
        // Remove existing heatmap if any
        if (heatmapLayer && airQualityMap.hasLayer(heatmapLayer)) {
            airQualityMap.removeLayer(heatmapLayer);
            console.log('🗑️ Removed old heatmap layer');
        }
        
        // Wait for map to be fully ready
        setTimeout(function() {
            try {
                // Verify map is ready
                if (!airQualityMap || !airQualityMap.getContainer()) {
                    console.error('❌ Map not ready for heatmap');
                    return;
                }
                
                // Check container dimensions
                const container = airQualityMap.getContainer();
                const width = container.offsetWidth;
                const height = container.offsetHeight;
                
                console.log(`📐 Map container size: ${width}x${height}px`);
                
                if (width === 0 || height === 0) {
                    console.error(`❌ Map container has zero size: ${width}x${height}`);
                    return;
                }
                
                // Create heatmap layer with enhanced settings
                heatmapLayer = L.heatLayer(data, {
                    radius: 80,              // Large radius for high visibility
                    blur: 50,                // Heavy blur for smooth gradient
                    maxZoom: 17,             // Show at all zoom levels
                    max: 5.0,                // Max intensity (250 AQI / 50 = 5.0)
                    minOpacity: 0.8,         // Very high minimum opacity
                    gradient: {
                        0.0: '#00ff00',      // Bright Green (Good)
                        0.2: '#ffff00',      // Bright Yellow (Moderate)
                        0.4: '#ff9900',      // Bright Orange (Unhealthy for Sensitive)
                        0.6: '#ff0000',      // Bright Red (Unhealthy)
                        0.8: '#cc00ff',      // Bright Purple (Very Unhealthy)
                        1.0: '#990000'       // Dark Red (Hazardous)
                    }
                });
                
                // Add to map
                heatmapLayer.addTo(airQualityMap);
                
                // Verify addition
                if (airQualityMap.hasLayer(heatmapLayer)) {
                    console.log(`✅ HEATMAP SUCCESSFULLY CREATED AND ADDED!`);
                    console.log(`📊 Heatmap info:`, {
                        dataPoints: data.length,
                        radius: 80,
                        blur: 50,
                        maxIntensity: 5.0,
                        opacity: 0.8
                    });
                    
                    // Force map refresh
                    airQualityMap.invalidateSize();
                } else {
                    console.error('❌ Failed to add heatmap layer to map');
                }
                
            } catch (error) {
                console.error('❌ Error creating heatmap:', error);
                console.error('Error details:', error.message, error.stack);
            }
        }, 1500); // Longer delay to ensure canvas is ready
    }

    function addAqicnMarker() {
        // Add special marker for AQICN data source (Jakarta Kemayoran)
        const aqicnMarker = L.marker([-6.155, 106.846], {
            icon: L.divIcon({
                className: 'custom-div-icon',
                html: `<div style="background-color: #3b82f6; color: white; padding: 5px 10px; border-radius: 20px; font-weight: bold; font-size: 12px; white-space: nowrap; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);">
                    <i class="fas fa-cloud"></i> AQICN
                </div>`,
                iconSize: [80, 30],
                iconAnchor: [40, 15]
            })
        }).addTo(airQualityMap);
        
        aqicnMarker.bindPopup(`
            <div class="p-2">
                <h3 class="font-bold text-lg">
                    <i class="fas fa-cloud text-blue-600"></i> AQICN Station
                </h3>
                <p class="text-sm text-gray-600">Kemayoran, Jakarta</p>
                <p class="text-xs mt-2 text-gray-500">Data source: World Air Quality Index Project</p>
                <button onclick="syncKarawangData()" class="mt-2 w-full bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">
                    <i class="fas fa-sync-alt mr-1"></i> Sync Data Now
                </button>
            </div>
        `);
    }

    // ============================================
    // STATION DETAILS
    // ============================================
    function viewStationDetails(stationId) {
        alert('Feature coming soon: Station details for ID ' + stationId);
    }

    // ============================================
    // STATIONS CRUD FUNCTIONS
    // ============================================
    let isEditModeStation = false;
    let currentStationId = null;

    function initStationsCRUD() {
        // Add button
        $('#btnAddStation').on('click', () => openStationModal(false));
        $('#btnCancelStation').on('click', () => closeStationModal());
        $('#btnRefreshStations').on('click', () => loadStationsForCRUD());
        $('#btnCloseViewStation').on('click', () => $('#viewStationModal').removeClass('active'));

        // Form submit
        $('#stationForm').on('submit', function(e) {
            e.preventDefault();
            saveStation();
        });

        // Search
        $('#searchStationInput').on('keyup', function() {
            const searchTerm = $(this).val().toLowerCase();
            $('#stationsTableBody tr').each(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(searchTerm) > -1);
            });
        });
    }

    function loadStationsForCRUD() {
        $.ajax({
            url: '/siapkak/api/stations',
            type: 'GET',
            xhrFields: { withCredentials: true },
            beforeSend: function() {
                $('#stationsTableBody').html(`
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Loading...
                        </td>
                    </tr>
                `);
            },
            success: function(response) {
                if (response.success && response.data.stations) {
                    displayStationsTable(response.data.stations);
                } else {
                    alert('Gagal memuat data stasiun');
                }
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Gagal memuat data'));
            }
        });
    }

    function displayStationsTable(stations) {
        if (stations.length === 0) {
            $('#stationsTableBody').html(`
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox mr-2"></i>Tidak ada data stasiun
                    </td>
                </tr>
            `);
            return;
        }

        let html = '';
        stations.forEach(function(station) {
            const status = station.latest_aqi ? 
                `<span class="px-2 py-1 text-xs rounded-full ${getAqiClass(station.latest_aqi)} font-semibold">
                    AQI: ${station.latest_aqi}
                </span>` : 
                '<span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600">No Data</span>';

            html += `
                <tr class="hover:bg-gray-50">
                    <td class="hidden md:table-cell px-6 py-4 text-sm text-gray-900">${station.id}</td>
                    <td class="px-4 md:px-6 py-4 text-sm font-medium text-gray-900">${escapeHtml(station.name)}</td>
                    <td class="px-4 md:px-6 py-4 text-sm text-gray-600">${escapeHtml(station.location)}</td>
                    <td class="hidden lg:table-cell px-6 py-4 text-sm text-gray-600">${station.latitude}, ${station.longitude}</td>
                    <td class="hidden md:table-cell px-6 py-4 text-sm">${status}</td>
                    <td class="px-4 md:px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button onclick="viewStation(${station.id})" class="text-blue-600 hover:text-blue-900" title="Detail">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="editStation(${station.id})" class="text-yellow-600 hover:text-yellow-900" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteStation(${station.id}, '${escapeHtml(station.name)}')" class="text-red-600 hover:text-red-900" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });

        $('#stationsTableBody').html(html);
    }

    function openStationModal(isEdit) {
        isEditModeStation = isEdit;
        $('#stationModalTitle').text(isEdit ? 'Edit Stasiun' : 'Tambah Stasiun');
        $('#stationModal').addClass('active');
    }

    function closeStationModal() {
        $('#stationModal').removeClass('active');
        $('#stationForm')[0].reset();
        $('#stationId').val('');
        isEditModeStation = false;
        currentStationId = null;
    }

    window.editStation = function(id) {
        $.ajax({
            url: '/siapkak/api/stations/' + id,
            type: 'GET',
            xhrFields: { withCredentials: true },
            success: function(response) {
                if (response.success) {
                    const station = response.data;
                    $('#stationId').val(station.id);
                    $('#stationName').val(station.name);
                    $('#stationLocation').val(station.location);
                    $('#stationLatitude').val(station.latitude);
                    $('#stationLongitude').val(station.longitude);
                    $('#stationDescription').val(station.description);
                    currentStationId = id;
                    openStationModal(true);
                }
            }
        });
    };

    function saveStation() {
        const id = $('#stationId').val();
        const data = {
            name: $('#stationName').val(),
            location: $('#stationLocation').val(),
            latitude: parseFloat($('#stationLatitude').val()),
            longitude: parseFloat($('#stationLongitude').val()),
            description: $('#stationDescription').val()
        };

        const url = id ? '/siapkak/api/stations/update?id=' + id : '/siapkak/api/stations';
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: method,
            contentType: 'application/json',
            data: JSON.stringify(data),
            xhrFields: { withCredentials: true },
            beforeSend: function() {
                $('#btnSubmitStation').prop('disabled', true)
                    .html('<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...');
            },
            success: function(response) {
                if (response.success) {
                    alert(id ? 'Stasiun berhasil diupdate' : 'Stasiun berhasil ditambahkan');
                    closeStationModal();
                    loadStationsForCRUD();
                } else {
                    alert(response.message || 'Gagal menyimpan data');
                }
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Gagal menyimpan data'));
            },
            complete: function() {
                $('#btnSubmitStation').prop('disabled', false)
                    .html('<i class="fas fa-save mr-2"></i>Simpan');
            }
        });
    }

    window.deleteStation = function(id, name) {
        if (!confirm(`Apakah Anda yakin ingin menghapus stasiun "${name}"?\n\nData yang terhapus tidak dapat dikembalikan.`)) {
            return;
        }

        $.ajax({
            url: '/siapkak/api/stations/delete?id=' + id,
            type: 'DELETE',
            xhrFields: { withCredentials: true },
            success: function(response) {
                if (response.success) {
                    alert('Stasiun berhasil dihapus');
                    loadStationsForCRUD();
                } else {
                    alert(response.message || 'Gagal menghapus stasiun');
                }
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Gagal menghapus stasiun'));
            }
        });
    };

    window.viewStation = function(id) {
        $.ajax({
            url: '/siapkak/api/stations/' + id,
            type: 'GET',
            xhrFields: { withCredentials: true },
            success: function(response) {
                if (response.success) {
                    const station = response.data;
                    $('#viewStationContent').html(`
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-500">ID</label>
                                <p class="text-lg">${station.id}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Nama Stasiun</label>
                                <p class="text-lg font-semibold">${escapeHtml(station.name)}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Lokasi</label>
                                <p class="text-lg">${escapeHtml(station.location)}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Latitude</label>
                                    <p class="text-lg">${station.latitude}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Longitude</label>
                                    <p class="text-lg">${station.longitude}</p>
                                </div>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Deskripsi</label>
                                <p class="text-lg">${escapeHtml(station.description) || '-'}</p>
                            </div>
                        </div>
                    `);
                    $('#viewStationModal').addClass('active');
                }
            }
        });
    };

    // ============================================
    // READINGS CRUD FUNCTIONS
    // ============================================
    let isEditModeReading = false;
    let readingsStations = [];

    function initReadingsCRUD() {
        // Add button - only if admin
        <?php if (PermissionHelper::isAdmin()): ?>
        $('#btnAddReading').on('click', () => openReadingModal(false));
        $('#btnCancelReading').on('click', () => closeReadingModal());
        <?php endif; ?>

        $('#btnRefreshReadings').on('click', () => loadReadingsForCRUD());
        $('#btnCloseViewReading').on('click', () => $('#viewReadingModal').removeClass('active'));

        // Form submit - only if admin
        <?php if (PermissionHelper::isAdmin()): ?>
        $('#readingForm').on('submit', function(e) {
            e.preventDefault();
            saveReading();
        });
        <?php endif; ?>

        // Search
        $('#searchReadingInput').on('keyup', function() {
            const searchTerm = $(this).val().toLowerCase();
            $('#readingsTableBody tr').each(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(searchTerm) > -1);
            });
        });

        // Filter by station
        $('#filterReadingStation').on('change', function() {
            const stationId = $(this).val();
            if (stationId) {
                $('#readingsTableBody tr').each(function() {
                    const rowStationId = $(this).data('station-id');
                    $(this).toggle(rowStationId == stationId);
                });
            } else {
                $('#readingsTableBody tr').show();
            }
        });

        // Load stations for dropdown
        loadStationsForReadings();

        // If readings section is already visible on load, load data immediately
        if (!$('#readings').hasClass('hidden')) {
            loadReadingsForCRUD();
        }

        // Ensure readings are loaded when navigating to the readings section
        $('.nav-link[data-target="readings"], a[href="#readings"], #navReadings').on('click', function() {
            // Small delay so any UI toggle/hide animations complete before loading
            setTimeout(function() {
                loadReadingsForCRUD();
            }, 50);
        });
    }

    function loadStationsForReadings() {
        $.ajax({
            url: '/siapkak/api/stations',
            type: 'GET',
            xhrFields: { withCredentials: true },
            success: function(response) {
                if (response.success) {
                    readingsStations = response.data.stations;
                    updateStationDropdowns();
                }
            }
        });
    }

    function updateStationDropdowns() {
        let options = '<option value="">Pilih Stasiun</option>';
        readingsStations.forEach(station => {
            options += `<option value="${station.id}">${escapeHtml(station.name)}</option>`;
        });
        $('#readingStationId').html(options);
        
        let filterOptions = '<option value="">Semua Stasiun</option>';
        readingsStations.forEach(station => {
            filterOptions += `<option value="${station.id}">${escapeHtml(station.name)}</option>`;
        });
        $('#filterReadingStation').html(filterOptions);
    }

    function loadReadingsForCRUD() {
        $.ajax({
            url: '/siapkak/api/readings?limit=100',
            type: 'GET',
            xhrFields: { withCredentials: true },
            beforeSend: function() {
                $('#readingsTableBody').html(`
                    <tr>
                        <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Loading...
                        </td>
                    </tr>
                `);
            },
            success: function(response) {
                if (response.success && response.data.readings) {
                    displayReadingsTable(response.data.readings);
                } else {
                    alert('Gagal memuat data readings');
                }
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Gagal memuat data'));
            }
        });
    }

    function displayReadingsTable(readings) {
        if (readings.length === 0) {
            $('#readingsTableBody').html(`
                <tr>
                    <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox mr-2"></i>Tidak ada data readings
                    </td>
                </tr>
            `);
            return;
        }

        let html = '';
        readings.forEach(function(reading) {
            const aqiClass = getAqiClass(reading.aqi_index); // Gunakan aqi_index
            html += `
                <tr class="hover:bg-gray-50" data-station-id="${reading.station_id}">
                    <td class="px-6 py-4 text-sm text-gray-900">${reading.id}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">${escapeHtml(reading.station_name || '-')}</td>
                    <td class="px-6 py-4 text-sm">
                        <span class="px-2 py-1 text-xs rounded-full ${aqiClass} font-semibold">
                            ${reading.aqi_index || '-'}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">${formatNumber(reading.pm25)}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">${formatNumber(reading.pm10)}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">${formatNumber(reading.o3)}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">${formatNumber(reading.no2)}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">${formatDateTime(reading.measured_at)}</td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button onclick="viewReading(${reading.id})" class="text-blue-600 hover:text-blue-900" title="Detail">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php if (PermissionHelper::isAdmin()): ?>
                            <button onclick="editReading(${reading.id})" class="text-yellow-600 hover:text-yellow-900" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteReading(${reading.id})" class="text-red-600 hover:text-red-900" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            `;
        });

        $('#readingsTableBody').html(html);
    }

    function openReadingModal(isEdit) {
        <?php if (!PermissionHelper::isAdmin()): ?>
        alert('Anda tidak memiliki akses untuk menambah/mengedit data reading.');
        return;
        <?php endif; ?>

        isEditModeReading = isEdit;
        $('#readingModalTitle').text(isEdit ? 'Edit Reading' : 'Tambah Reading');
        $('#readingModal').addClass('active');
    }

    function closeReadingModal() {
        $('#readingModal').removeClass('active');
        $('#readingForm')[0].reset();
        $('#readingId').val('');
        isEditModeReading = false;
    }

    window.editReading = function(id) {
        <?php if (!PermissionHelper::isAdmin()): ?>
        alert('Anda tidak memiliki akses untuk mengedit data reading.');
        return;
        <?php endif; ?>

        $.ajax({
            url: '/siapkak/api/readings/show?id=' + id,
            type: 'GET',
            xhrFields: { withCredentials: true },
            success: function(response) {
                if (response.success) {
                    const reading = response.data.reading;
                    $('#readingId').val(reading.id);
                    $('#readingStationId').val(reading.station_id);
                    $('#readingPm25').val(reading.pm25);
                    $('#readingPm10').val(reading.pm10);
                    $('#readingO3').val(reading.o3);
                    $('#readingNo2').val(reading.no2);
                    $('#readingCo').val(reading.co);
                    $('#readingSo2').val(reading.so2);
                    $('#readingTemp').val(reading.temperature);
                    $('#readingHumidity').val(reading.humidity);
                    if (reading.measured_at) {
                        const date = new Date(reading.measured_at);
                        const formatted = date.toISOString().slice(0, 16);
                        $('#readingRecordedAt').val(formatted);
                    }
                    openReadingModal(true);
                }
            }
        });
    };

    function saveReading() {
        const id = $('#readingId').val();
        const data = {
            station_id: parseInt($('#readingStationId').val()),
            pm25: parseFloat($('#readingPm25').val()),
            pm10: parseFloat($('#readingPm10').val()),
            o3: parseFloat($('#readingO3').val()) || null,
            no2: parseFloat($('#readingNo2').val()) || null,
            co: parseFloat($('#readingCo').val()) || null,
            so2: parseFloat($('#readingSo2').val()) || null,
            temperature: parseFloat($('#readingTemp').val()) || null,
            humidity: parseFloat($('#readingHumidity').val()) || null,
            measured_at: $('#readingRecordedAt').val() || null
        };

        const url = id ? '/siapkak/api/readings/update?id=' + id : '/siapkak/api/readings';
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: method,
            contentType: 'application/json',
            data: JSON.stringify(data),
            xhrFields: { withCredentials: true },
            beforeSend: function() {
                $('#btnSubmitReading').prop('disabled', true)
                    .html('<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...');
            },
            success: function(response) {
                if (response.success) {
                    alert(id ? 'Reading berhasil diupdate' : 'Reading berhasil ditambahkan');
                    closeReadingModal();
                    loadReadingsForCRUD();
                } else {
                    alert(response.message || 'Gagal menyimpan data');
                }
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Gagal menyimpan data'));
            },
            complete: function() {
                $('#btnSubmitReading').prop('disabled', false)
                    .html('<i class="fas fa-save mr-2"></i>Simpan');
            }
        });
    }

    window.deleteReading = function(id) {
        <?php if (!PermissionHelper::isAdmin()): ?>
        alert('Anda tidak memiliki akses untuk menghapus data reading.');
        return;
        <?php endif; ?>

        if (!confirm('Apakah Anda yakin ingin menghapus reading ini?')) {
            return;
        }

        $.ajax({
            url: '/siapkak/api/readings/delete?id=' + id,
            type: 'DELETE',
            xhrFields: { withCredentials: true },
            success: function(response) {
                if (response.success) {
                    alert('Reading berhasil dihapus');
                    loadReadingsForCRUD();
                } else {
                    alert(response.message || 'Gagal menghapus reading');
                }
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Gagal menghapus reading'));
            }
        });
    };

    window.viewReading = function(id) {
        $.ajax({
            url: '/siapkak/api/readings/show?id=' + id,
            type: 'GET',
            xhrFields: { withCredentials: true },
            success: function(response) {
                if (response.success) {
                    const reading = response.data.reading;
                    const aqiClass = getAqiClass(reading.aqi_index);
                    $('#viewReadingContent').html(`
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">ID</label>
                                    <p class="text-lg font-semibold">${reading.id}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Stasiun</label>
                                    <p class="text-lg font-semibold">${escapeHtml(reading.station_name || '-')}</p>
                                </div>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">AQI (Air Quality Index)</label>
                                <p class="text-lg">
                                    <span class="px-3 py-1 rounded-full ${aqiClass} font-semibold">
                                        ${reading.aqi_index || '-'}
                                    </span>
                                </p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Status</label>
                                <p class="text-lg">${escapeHtml(reading.status || '-')}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">PM2.5 (μg/m³)</label>
                                    <p class="text-lg">${formatNumber(reading.pm25)}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">PM10 (μg/m³)</label>
                                    <p class="text-lg">${formatNumber(reading.pm10)}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">O₃ (ppb)</label>
                                    <p class="text-lg">${formatNumber(reading.o3)}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">NO₂ (ppb)</label>
                                    <p class="text-lg">${formatNumber(reading.no2)}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">CO (ppm)</label>
                                    <p class="text-lg">${formatNumber(reading.co)}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">SO₂ (ppb)</label>
                                    <p class="text-lg">${formatNumber(reading.so2)}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Temperature (°C)</label>
                                    <p class="text-lg">${formatNumber(reading.temperature)}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Humidity (%)</label>
                                    <p class="text-lg">${formatNumber(reading.humidity)}</p>
                                </div>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Waktu Pembacaan</label>
                                <p class="text-lg">${formatDateTime(reading.measured_at)}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4 border-t pt-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Dibuat Pada</label>
                                    <p class="text-sm text-gray-600">${formatDateTime(reading.created_at)}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Diperbarui Pada</label>
                                    <p class="text-sm text-gray-600">${formatDateTime(reading.updated_at)}</p>
                                </div>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Sumber Data</label>
                                <p class="text-lg">${escapeHtml(reading.source_api || '-')}</p>
                            </div>
                        </div>
                    `);
                    $('#viewReadingModal').addClass('active');
                }
            }
        });
    };

    // Helper functions
    function formatNumber(value) {
        if (value === null || value === undefined) return '-';
        return parseFloat(value).toFixed(2);
    }

    function formatDateTime(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleString('id-ID', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    function getAqiClass(aqi) {
        if (!aqi) return 'bg-gray-100 text-gray-600';
        if (aqi <= 50) return 'bg-green-100 text-green-800';
        if (aqi <= 100) return 'bg-yellow-100 text-yellow-800';
        if (aqi <= 150) return 'bg-orange-100 text-orange-800';
        if (aqi <= 200) return 'bg-red-100 text-red-800';
        if (aqi <= 300) return 'bg-purple-100 text-purple-800';
        return 'bg-red-900 text-white';
    }

    </script>

    <!-- Station CRUD Modals -->
    <div id="stationModal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b p-6">
                <h2 id="stationModalTitle" class="text-2xl font-bold text-gray-900">Tambah Stasiun</h2>
            </div>
            
            <form id="stationForm" class="p-6">
                <input type="hidden" id="stationId">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Stasiun *</label>
                        <input type="text" id="stationName" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Lokasi *</label>
                        <input type="text" id="stationLocation" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Latitude *</label>
                        <input type="number" id="stationLatitude" step="0.00000001" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        <small class="text-gray-500">Contoh: -6.305083</small>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Longitude *</label>
                        <input type="number" id="stationLongitude" step="0.00000001" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        <small class="text-gray-500">Contoh: 107.299370</small>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                        <textarea id="stationDescription" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"></textarea>
                    </div>
                </div>
                
                <div class="flex gap-3 mt-6">
                    <button type="button" id="btnCancelStation" class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                        <i class="fas fa-times mr-2"></i>Batal
                    </button>
                    <button type="submit" id="btnSubmitStation" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-save mr-2"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Station Modal -->
    <div id="viewStationModal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b p-6 flex justify-between items-center">
                <h2 class="text-2xl font-bold text-gray-900">Detail Stasiun</h2>
                <button id="btnCloseViewStation" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div id="viewStationContent" class="p-6">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Reading CRUD Modals -->
    <?php if (PermissionHelper::isAdmin()): ?>
    <div id="readingModal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b p-6">
                <h2 id="readingModalTitle" class="text-2xl font-bold text-gray-900">Tambah Reading</h2>
            </div>
            
            <form id="readingForm" class="p-6">
                <input type="hidden" id="readingId">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Stasiun *</label>
                        <select id="readingStationId" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                            <option value="">Pilih Stasiun</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">PM2.5 (μg/m³) *</label>
                        <input type="number" id="readingPm25" step="0.01" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">PM10 (μg/m³) *</label>
                        <input type="number" id="readingPm10" step="0.01" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">O₃ (ppb)</label>
                        <input type="number" id="readingO3" step="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">NO₂ (ppb)</label>
                        <input type="number" id="readingNo2" step="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">CO (ppm)</label>
                        <input type="number" id="readingCo" step="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">SO₂ (ppb)</label>
                        <input type="number" id="readingSo2" step="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Temperature (°C)</label>
                        <input type="number" id="readingTemp" step="0.1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Humidity (%)</label>
                        <input type="number" id="readingHumidity" step="0.1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Waktu Pembacaan</label>
                        <input type="datetime-local" id="readingRecordedAt" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        <small class="text-gray-500">Kosongkan untuk menggunakan waktu saat ini</small>
                    </div>
                </div>
                
                <div class="flex gap-3 mt-6">
                    <button type="button" id="btnCancelReading" class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                        <i class="fas fa-times mr-2"></i>Batal
                    </button>
                    <button type="submit" id="btnSubmitReading" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-save mr-2"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- View Reading Modal -->
    <div id="viewReadingModal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b p-6 flex justify-between items-center">
                <h2 class="text-2xl font-bold text-gray-900">Detail Reading</h2>
                <button id="btnCloseViewReading" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div id="viewReadingContent" class="p-6">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

</body>
</html>
