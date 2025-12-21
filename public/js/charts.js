// Chart.js initialization dan management

class ChartManager {
    constructor() {
        this.charts = {};
        this.apiBaseUrl = '/siapkak/api';
    }

    /**
     * Initialize all charts pada dashboard
     */
    async initializeCharts() {
        try {
            // Initialize time-series chart
            await this.initTimeSeriesChart();
            
            // Initialize status distribution chart
            await this.initStatusDistributionChart();
        } catch (error) {
            console.error('Failed to initialize charts:', error);
        }
    }

    /**
     * Initialize time-series AQI trend chart
     */
    async initTimeSeriesChart(stationId = null) {
        try {
            // Get default station ID jika tidak ada
            if (!stationId) {
                const stationsResponse = await fetch(`${this.apiBaseUrl}/stations`);
                const stationsData = await stationsResponse.json();
                if (stationsData.data && stationsData.data.stations && stationsData.data.stations.length > 0) {
                    stationId = stationsData.data.stations[0].id;
                } else {
                    return;
                }
            }

            // Fetch chart data
            const response = await fetch(`${this.apiBaseUrl}/charts/time-series?station_id=${stationId}`);
            const data = await response.json();

            if (!data.success) {
                console.error('Failed to fetch time series data:', data);
                return;
            }

            const chartData = data.data;
            const ctx = document.getElementById('aqiTrendChart');
            
            if (!ctx) return;

            // Destroy existing chart
            if (this.charts.timeSeries) {
                this.charts.timeSeries.destroy();
            }

            // Create new chart
            this.charts.timeSeries = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: chartData.datasets
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        title: {
                            display: true,
                            text: `AQI Trend - ${chartData.station.name} (24 Jam Terakhir)`
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'AQI Index'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Waktu'
                            }
                        }
                    }
                }
            });

            console.log('Time-series chart initialized');
        } catch (error) {
            console.error('Failed to initialize time-series chart:', error);
        }
    }

    /**
     * Initialize status distribution chart (pie/bar)
     */
    async initStatusDistributionChart() {
        try {
            // Fetch chart data
            const response = await fetch(`${this.apiBaseUrl}/charts/status-distribution`);
            const data = await response.json();

            if (!data.success) {
                console.error('Failed to fetch status distribution data:', data);
                return;
            }

            const chartData = data.data;
            const ctx = document.getElementById('statusDistributionChart');
            
            if (!ctx) return;

            // Destroy existing chart
            if (this.charts.statusDistribution) {
                this.charts.statusDistribution.destroy();
            }

            // Create new chart (Doughnut chart)
            this.charts.statusDistribution = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: chartData.labels,
                    datasets: chartData.datasets
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Distribusi Status Kualitas Udara'
                        }
                    }
                }
            });

            console.log('Status distribution chart initialized');
        } catch (error) {
            console.error('Failed to initialize status distribution chart:', error);
        }
    }

    /**
     * Update time-series chart dengan station baru
     */
    async updateTimeSeriesChart(stationId) {
        await this.initTimeSeriesChart(stationId);
    }

    /**
     * Refresh semua charts
     */
    async refreshCharts(stationId = null) {
        await this.initTimeSeriesChart(stationId);
        await this.initStatusDistributionChart();
    }

    /**
     * Auto-refresh charts setiap 5 menit
     */
    startAutoRefresh(interval = 300000) { // 5 minutes default
        this.autoRefreshInterval = setInterval(() => {
            console.log('Auto-refreshing charts...');
            this.refreshCharts();
        }, interval);
    }

    /**
     * Stop auto-refresh
     */
    stopAutoRefresh() {
        if (this.autoRefreshInterval) {
            clearInterval(this.autoRefreshInterval);
        }
    }

    /**
     * Get statistics untuk dashboard
     */
    async getStatistics() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/charts/statistics`);
            const data = await response.json();

            if (data.success) {
                return data.data.statistics;
            }
            return null;
        } catch (error) {
            console.error('Failed to get statistics:', error);
            return null;
        }
    }

    /**
     * Display statistics di dashboard
     */
    async displayStatistics() {
        const stats = await this.getStatistics();
        
        if (!stats) return;

        // Update dashboard elements
        const elements = {
            'dashTotalStations': stats.total_stations,
            'dashTotalReadings': stats.total_readings,
            'dashAvgAQI': Math.round(stats.average_aqi),
            'dashMaxAQI': stats.max_aqi,
            'dashHealthy': Math.round(stats.healthy_percentage)
        };

        for (const [id, value] of Object.entries(elements)) {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value;
            }
        }
    }
}

// Global chart manager instance
const chartManager = new ChartManager();

// Initialize charts when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Wait for dashboard to be loaded
    setTimeout(() => {
        chartManager.initializeCharts();
        chartManager.displayStatistics();
        chartManager.startAutoRefresh(300000); // Auto-refresh every 5 minutes
    }, 1000);
});
