<?php
// Require authentication and permission
require_once __DIR__ . '/../vendor/autoload.php';
use App\Config\PermissionHelper;

PermissionHelper::init();
PermissionHelper::requireAuth();
PermissionHelper::requireAccess('reports');

$breadcrumbs = [
    ['label' => 'Laporan']
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kualitas Udara - SIAPKAK</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="https://res.cloudinary.com/drgwsncdn/image/upload/v1764506518/logo_kuck1i.png">
    <link rel="shortcut icon" type="image/png" href="https://res.cloudinary.com/drgwsncdn/image/upload/v1764506518/logo_kuck1i.png">
    <link rel="apple-touch-icon" href="https://res.cloudinary.com/drgwsncdn/image/upload/v1764506518/logo_kuck1i.png">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .report-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .report-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .loading {
            border-top-color: #3498db;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        /* Sidebar transition */
        .sidebar { 
            transition: transform 0.3s ease-in-out; 
        }
        .sidebar-overlay { 
            transition: opacity 0.3s ease-in-out;
            pointer-events: auto;
        }
        .sidebar-overlay.hidden {
            pointer-events: none;
        }
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
        @media (max-width: 1023px) {
            .sidebar {
                position: fixed;
                left: 0;
                top: 64px;
                bottom: 0;
                transform: translateX(-100%);
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
            <?php include __DIR__ . '/../src/views/breadcrumb.php'; ?>
            
            <div class="mb-6">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Laporan Kualitas Udara</h2>
                <p class="text-sm sm:text-base text-gray-600 mt-1">Generate dan download laporan kualitas udara berdasarkan periode dan wilayah</p>
            </div>

        <!-- Report Preview Section -->
        <div id="reportPreviewSection">
            <!-- Summary Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6 mb-6 sm:mb-8">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-xs sm:text-sm">Total Readings</p>
                            <p id="totalReadings" class="text-2xl sm:text-3xl font-bold text-blue-600">0</p>
                        </div>
                        <i class="fas fa-database text-2xl sm:text-4xl text-blue-200"></i>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-xs sm:text-sm">Rata-rata AQI</p>
                            <p id="avgAQI" class="text-2xl sm:text-3xl font-bold text-green-600">0</p>
                        </div>
                        <i class="fas fa-chart-line text-2xl sm:text-4xl text-green-200"></i>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-xs sm:text-sm">AQI Tertinggi</p>
                            <p id="maxAQI" class="text-2xl sm:text-3xl font-bold text-red-600">0</p>
                        </div>
                        <i class="fas fa-arrow-up text-2xl sm:text-4xl text-red-200"></i>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-xs sm:text-sm">AQI Terendah</p>
                            <p id="minAQI" class="text-2xl sm:text-3xl font-bold text-blue-600">0</p>
                        </div>
                        <i class="fas fa-arrow-down text-2xl sm:text-4xl text-blue-200"></i>
                    </div>
                </div>
            </div>

            <!-- Download Buttons -->
            <div class="bg-white rounded-lg shadow-md p-4 sm:p-6 mb-6 sm:mb-8">
                <h3 class="text-lg sm:text-xl font-semibold text-gray-800 mb-4">
                    <i class="fas fa-download mr-2"></i>Download Laporan
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <button id="downloadCSV" class="bg-green-600 hover:bg-green-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg transition flex items-center justify-center">
                        <i class="fas fa-file-csv mr-2"></i><span class="text-sm sm:text-base">Download CSV</span>
                    </button>
                    <button id="downloadExcel" class="bg-blue-600 hover:bg-blue-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg transition flex items-center justify-center">
                        <i class="fas fa-file-excel mr-2"></i><span class="text-sm sm:text-base">Download Excel (XLSX)</span>
                    </button>
                    <button id="downloadPDF" class="bg-red-600 hover:bg-red-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg transition flex items-center justify-center">
                        <i class="fas fa-file-pdf mr-2"></i><span class="text-sm sm:text-base">Download PDF</span>
                    </button>
                </div>
            </div>

            <!-- Report Table -->
            <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                <h3 class="text-lg sm:text-xl font-semibold text-gray-800 mb-4">
                    <i class="fas fa-table mr-2"></i>Data Laporan
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stasiun</th>
                                <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lokasi</th>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">AQI</th>
                                <th class="hidden lg:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PM2.5</th>
                                <th class="hidden lg:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PM10</th>
                                <th class="hidden lg:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">O3</th>
                                <th class="hidden lg:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NO2</th>
                                <th class="hidden lg:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SO2</th>
                                <th class="hidden lg:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CO</th>
                            </tr>
                        </thead>
                        <tbody id="reportTableBody" class="bg-white divide-y divide-gray-200">
                            <!-- Data will be populated here -->
                        </tbody>
                    </table>
                </div>
                <div id="noDataMessage" class="text-center py-8 text-gray-500 hidden">
                    <i class="fas fa-inbox text-5xl mb-3"></i>
                    <p>Tidak ada data untuk ditampilkan</p>
                </div>
            </div>
        </div>

        <!-- Initial Message (hidden by default now) -->
        <div id="initialMessage" class="bg-white rounded-lg shadow-md p-12 text-center hidden">
            <i class="fas fa-file-chart-line text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">Pilih Filter dan Generate Laporan</h3>
            <p class="text-gray-500">Gunakan filter di atas untuk membuat laporan kualitas udara</p>
            </div>
        </main>
    </div>

    <!-- Alert Container -->
    <div id="alertContainer" class="fixed top-4 right-4 z-50"></div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        console.log('Reports page loaded');
        
        // Initialize accordion for sidebar
        initAccordion();
        
        // Initialize sidebar toggle
        initSidebar();
        
        let currentReportData = null;
        let allReadingsData = null;

        // Set default date to today
        $('#reportDate').val(new Date().toISOString().split('T')[0]);

        // Load cities/stations
        loadCities();
        
        // Load all data on page load
        loadAllData();

        function checkAuth() {
            $.ajax({
                url: '/siapkak/api/auth/verify',
                type: 'GET',
                xhrFields: { withCredentials: true },
                error: function() {
                    console.log('Not authenticated, redirecting to login');
                    window.location.href = '/siapkak/public/login.html';
                }
            });
        }

        // Apply Filter
        $('#applyFilterBtn').on('click', function() {
            applyFilter();
        });

        // Refresh/Reset
        $('#refreshBtn').on('click', function() {
            $('#cityFilter').val('');
            $('#reportType').val('daily');
            $('#reportDate').val(new Date().toISOString().split('T')[0]);
            loadAllData();
        });

        // Download buttons
        $('#downloadCSV').on('click', function() {
            downloadReport('csv');
        });

        $('#downloadExcel').on('click', function() {
            downloadReport('xlsx');
        });

        $('#downloadPDF').on('click', function() {
            downloadReport('pdf');
        });

        // Logout
        $('#logoutBtn').on('click', function(e) {
            e.preventDefault();
            $.post('/siapkak/public/auth/logout.php', function() {
                window.location.href = '/siapkak/public/login.html';
            });
        });

        // Load user info
        $.ajax({
            url: '/siapkak/api/auth/me',
            type: 'GET',
            xhrFields: { withCredentials: true },
            success: function(response) {
                if (response.success && response.data) {
                    $('#userName').text(response.data.name || 'User');
                    $('#userEmail').text(response.data.email || '');
                }
            }
        });

        // Change password button
        $('#changePasswordBtn').on('click', function() {
            window.location.href = '/siapkak/dashboard#settings';
        });

        // Notification button
        $('#notificationBtn').on('click', function() {
            showAlert('Fitur notifikasi akan segera hadir', 'info');
        });

        function loadAllData() {
            console.log('Loading all readings data...');
            const btn = $('#applyFilterBtn');
            const originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Loading...');
            
            $.ajax({
                url: '/siapkak/api/readings?limit=10000',
                type: 'GET',
                xhrFields: { withCredentials: true },
                success: function(response) {
                    console.log('All data loaded:', response);
                    if (response.success && response.data && response.data.readings) {
                        allReadingsData = response.data.readings;
                        displayAllData(allReadingsData);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Failed to load data:', status, error);
                    showAlert('Gagal memuat data', 'error');
                },
                complete: function() {
                    btn.prop('disabled', false).html(originalHtml);
                }
            });
        }
        
        function applyFilter() {
            if (!allReadingsData) {
                showAlert('Data belum dimuat', 'warning');
                return;
            }
            
            const stationId = $('#cityFilter').val();
            const reportType = $('#reportType').val();
            const selectedDate = new Date($('#reportDate').val());
            
            let filteredData = allReadingsData;
            
            // Filter by station
            if (stationId) {
                filteredData = filteredData.filter(reading => reading.station_id == stationId);
            }
            
            // Filter by date range based on report type
            filteredData = filteredData.filter(reading => {
                const readingDate = new Date(reading.measured_at);
                
                switch(reportType) {
                    case 'daily':
                        return readingDate.toDateString() === selectedDate.toDateString();
                    case 'weekly':
                        const weekStart = new Date(selectedDate);
                        weekStart.setDate(selectedDate.getDate() - selectedDate.getDay());
                        const weekEnd = new Date(weekStart);
                        weekEnd.setDate(weekStart.getDate() + 6);
                        return readingDate >= weekStart && readingDate <= weekEnd;
                    case 'monthly':
                        return readingDate.getMonth() === selectedDate.getMonth() && 
                               readingDate.getFullYear() === selectedDate.getFullYear();
                    case 'yearly':
                        return readingDate.getFullYear() === selectedDate.getFullYear();
                    default:
                        return true;
                }
            });
            
            displayAllData(filteredData);
            showAlert('Filter diterapkan', 'success');
        }
        
        function displayAllData(readings) {
            // Calculate summary
            const total = readings.length;
            const aqiValues = readings.map(r => parseFloat(r.aqi) || 0).filter(v => v > 0);
            const avgAqi = aqiValues.length > 0 ? (aqiValues.reduce((a, b) => a + b, 0) / aqiValues.length).toFixed(1) : 0;
            const maxAqi = aqiValues.length > 0 ? Math.max(...aqiValues).toFixed(1) : 0;
            const minAqi = aqiValues.length > 0 ? Math.min(...aqiValues).toFixed(1) : 0;
            
            currentReportData = {
                summary: {
                    total_readings: total,
                    avg_aqi: avgAqi,
                    max_aqi: maxAqi,
                    min_aqi: minAqi
                },
                readings: readings
            };
            
            displayReport(currentReportData);
        }

        function loadCities() {
            console.log('Loading stations...');
            $.ajax({
                url: '/siapkak/api/stations?limit=1000',
                type: 'GET',
                xhrFields: { withCredentials: true },
                success: function(response) {
                    console.log('Stations loaded:', response);
                    if (response.success && response.data && response.data.stations) {
                        const select = $('#cityFilter');
                        // Get unique cities from stations
                        const cities = {};
                        response.data.stations.forEach(function(station) {
                            const city = station.location || station.city || 'Unknown';
                            if (!cities[city]) {
                                cities[city] = [];
                            }
                            cities[city].push(station);
                        });
                        
                        // Add options grouped by city
                        Object.keys(cities).sort().forEach(function(city) {
                            const optgroup = $(`<optgroup label="${city}"></optgroup>`);
                            cities[city].forEach(function(station) {
                                optgroup.append(`<option value="${station.id}">${station.name}</option>`);
                            });
                            select.append(optgroup);
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Failed to load stations:', status, error);
                    showAlert('Gagal memuat daftar stasiun', 'error');
                }
            });
        }

        function displayReport(data) {
            // Show report section
            $('#reportPreviewSection').removeClass('hidden');

            // Update summary cards
            $('#totalReadings').text(data.summary.total_readings || 0);
            $('#avgAQI').text(data.summary.avg_aqi || 0);
            $('#maxAQI').text(data.summary.max_aqi || 0);
            $('#minAQI').text(data.summary.min_aqi || 0);

            // Populate table
            const tbody = $('#reportTableBody');
            tbody.empty();

            if (data.readings && data.readings.length > 0) {
                $('#noDataMessage').addClass('hidden');
                data.readings.forEach(function(reading) {
                    const statusClass = getAQIStatusClass(reading.aqi);
                    const row = `
                        <tr>
                            <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ${formatDateTime(reading.measured_at)}
                            </td>
                            <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ${reading.station_name || '-'}
                            </td>
                            <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                ${reading.station_location || '-'}
                            </td>
                            <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-semibold ${statusClass}">
                                ${reading.aqi || '-'}
                            </td>
                            <td class="hidden lg:table-cell px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full ${statusClass}">
                                    ${reading.aqi_status || '-'}
                                </span>
                            </td>
                            <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-500">${reading.pm25 || '-'}</td>
                            <td class="hidden lg:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-500">${reading.pm10 || '-'}</td>
                            <td class="hidden lg:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-500">${reading.o3 || '-'}</td>
                            <td class="hidden lg:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-500">${reading.no2 || '-'}</td>
                            <td class="hidden lg:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-500">${reading.so2 || '-'}</td>
                            <td class="hidden lg:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-500">${reading.co || '-'}</td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            } else {
                $('#noDataMessage').removeClass('hidden');
            }
        }

        function downloadReport(format) {
            if (!currentReportData) {
                showAlert('Tidak ada data untuk didownload. Generate laporan terlebih dahulu.', 'warning');
                return;
            }

            const cityId = $('#cityFilter').val();
            const reportType = $('#reportType').val();
            const date = $('#reportDate').val();

            // Create download URL
            const params = new URLSearchParams({
                station_id: cityId || '',
                report_type: reportType,
                date: date,
                format: format
            });

            const downloadUrl = `/siapkak/api/reports/download?${params.toString()}`;
            
            // Trigger download
            window.location.href = downloadUrl;
            showAlert(`Downloading laporan dalam format ${format.toUpperCase()}...`, 'info');
        }

        function getAQIStatusClass(aqi) {
            if (aqi <= 50) return 'text-green-600';
            if (aqi <= 100) return 'text-yellow-600';
            if (aqi <= 150) return 'text-orange-600';
            if (aqi <= 200) return 'text-red-600';
            if (aqi <= 300) return 'text-purple-600';
            return 'text-red-900';
        }

        function formatDateTime(datetime) {
            if (!datetime) return '-';
            const date = new Date(datetime);
            return date.toLocaleString('id-ID', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function showAlert(message, type) {
            const alertClass = type === 'success' ? 'bg-green-500' : 
                              type === 'error' ? 'bg-red-500' : 
                              type === 'warning' ? 'bg-yellow-500' : 
                              'bg-blue-500';
            
            const iconClass = type === 'success' ? 'check-circle' : 
                             type === 'error' ? 'times-circle' : 
                             type === 'warning' ? 'exclamation-triangle' : 
                             'info-circle';
            
            const alert = $(`
                <div class="${alertClass} text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3 mb-3">
                    <i class="fas fa-${iconClass}"></i>
                    <span>${message}</span>
                </div>
            `);
            
            $('#alertContainer').append(alert);
            
            setTimeout(function() {
                alert.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    });

    // Accordion functionality for sidebar
    function initAccordion() {
        $('.accordion-toggle').on('click', function() {
            const content = $(this).next('.accordion-content');
            const icon = $(this).find('.accordion-icon');
            
            // Toggle current
            content.toggleClass('open');
            icon.toggleClass('rotate-180');
        });
    }

    // Sidebar toggle for mobile
    function initSidebar() {
        const sidebar = $('#sidebar');
        const overlay = $('#sidebarOverlay');
        const toggle = $('#sidebarToggle');

        toggle.on('click', function(e) {
            e.preventDefault();
            sidebar.toggleClass('open');
            overlay.toggleClass('hidden');
        });

        overlay.on('click', function(e) {
            e.preventDefault();
            sidebar.removeClass('open');
            overlay.addClass('hidden');
        });
    }
    </script>
</body>
</html>
