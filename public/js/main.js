// API Configuration
const API_BASE_URL = 'http://localhost/siapkak';
const API_ENDPOINTS = {
    AUTH: {
        REGISTER: '/api/auth/register',
        LOGIN: '/api/auth/login',
        ME: '/api/auth/me',
        LOGOUT: '/api/auth/logout'
    },
    STATIONS: {
        GET_ALL: '/api/stations',
        GET_ONE: '/api/stations/show',
        CREATE: '/api/stations',
        UPDATE: '/api/stations/update',
        DELETE: '/api/stations/delete'
    },
    READINGS: {
        GET_ALL: '/api/readings',
        GET_BY_STATION: '/api/readings/by-station',
        GET_TREND: '/api/readings/trend',
        CREATE: '/api/readings',
        UPDATE: '/api/readings/update',
        DELETE: '/api/readings/delete',
        SYNC_AQICN: '/api/readings/sync-aqicn'
    }
};

// Storage
class Storage {
    static setToken(token) {
        localStorage.setItem('auth_token', token);
    }

    static getToken() {
        return localStorage.getItem('auth_token');
    }

    static removeToken() {
        localStorage.removeItem('auth_token');
    }

    static isAuthenticated() {
        return !!this.getToken();
    }
}

// API Client
class ApiClient {
    static async request(endpoint, options = {}) {
        const url = `${API_BASE_URL}${endpoint}`;
        const headers = {
            'Content-Type': 'application/json',
            ...options.headers
        };

        const token = Storage.getToken();
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        try {
            const response = await fetch(url, {
                ...options,
                headers
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'API Error');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    static get(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        return this.request(url, { method: 'GET' });
    }

    static post(endpoint, body) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(body)
        });
    }

    static put(endpoint, body) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(body)
        });
    }

    static delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }
}

// UI Manager
class UIManager {
    static showModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
    }

    static hideModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    static showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg text-white z-50 ${
            type === 'error' ? 'bg-red-500' : type === 'success' ? 'bg-green-500' : 'bg-blue-500'
        }`;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    static getAqiColor(status) {
        const colors = {
            'Baik': 'bg-green-500',
            'Sedang': 'bg-yellow-500',
            'Tidak Sehat untuk Kelompok Sensitif': 'bg-orange-500',
            'Tidak Sehat': 'bg-red-500',
            'Sangat Tidak Sehat': 'bg-purple-600',
            'Berbahaya': 'bg-purple-900'
        };
        return colors[status] || 'bg-gray-500';
    }

    static getAqiBadgeClass(status) {
        const classes = {
            'Baik': 'bg-green-100 text-green-800',
            'Sedang': 'bg-yellow-100 text-yellow-800',
            'Tidak Sehat untuk Kelompok Sensitif': 'bg-orange-100 text-orange-800',
            'Tidak Sehat': 'bg-red-100 text-red-800',
            'Sangat Tidak Sehat': 'bg-purple-100 text-purple-800',
            'Berbahaya': 'bg-purple-900 text-white'
        };
        return classes[status] || 'bg-gray-100 text-gray-800';
    }
}

// Data Manager
class DataManager {
    static async loadStations() {
        try {
            const response = await ApiClient.get(API_ENDPOINTS.STATIONS.GET_ALL);
            return response.data.stations;
        } catch (error) {
            UIManager.showNotification(error.message, 'error');
            return [];
        }
    }

    static async loadStatistics() {
        try {
            const stations = await this.loadStations();
            const totalStations = stations.length;
            const goodAir = stations.filter(s => {
                const aqi = s.latest_aqi || 0;
                return aqi <= 100;
            }).length;
            const unhealthyAir = stations.filter(s => {
                const aqi = s.latest_aqi || 0;
                return aqi >= 150;
            }).length;

            document.getElementById('totalStations').textContent = totalStations;
            document.getElementById('goodAirCount').textContent = goodAir;
            document.getElementById('unhealthyAirCount').textContent = unhealthyAir;
        } catch (error) {
            console.error('Failed to load statistics:', error);
        }
    }

    static async displayStations(stations) {
        const stationsList = document.getElementById('stationsList');
        
        if (!stations || stations.length === 0) {
            stationsList.innerHTML = '<div class="p-6 text-center text-gray-500">Tidak ada stasiun tersedia</div>';
            return;
        }

        stationsList.innerHTML = stations.map(station => {
            const aqi = station.latest_aqi || 0;
            const status = station.latest_status || 'Unknown';
            const badgeClass = UIManager.getAqiBadgeClass(status);

            return `
                <div class="p-6 hover:bg-gray-50 cursor-pointer transition">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900">${station.name}</h4>
                            <p class="text-sm text-gray-600">${station.location}</p>
                        </div>
                        <div class="text-right">
                            <div class="text-3xl font-bold text-gray-900">${aqi}</div>
                            <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold ${badgeClass}">
                                ${status}
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="text-gray-600">PM2.5</p>
                            <p class="font-semibold">${(station.latest_pm25 || 0).toFixed(1)} µg/m³</p>
                        </div>
                        <div>
                            <p class="text-gray-600">PM10</p>
                            <p class="font-semibold">${(station.latest_pm10 || 0).toFixed(1)} µg/m³</p>
                        </div>
                        <div>
                            <p class="text-gray-600">O₃</p>
                            <p class="font-semibold">${(station.latest_o3 || 0).toFixed(1)} ppb</p>
                        </div>
                        <div>
                            <p class="text-gray-600">NO₂</p>
                            <p class="font-semibold">${(station.latest_no2 || 0).toFixed(1)} ppb</p>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-4">Update: ${new Date(station.latest_measured_at).toLocaleString('id-ID')}</p>
                </div>
            `;
        }).join('');
    }
}

// Auth Manager
class AuthManager {
    static async register(name, email, password) {
        try {
            const response = await ApiClient.post(API_ENDPOINTS.AUTH.REGISTER, {
                name, email, password
            });
            Storage.setToken(response.data.token);
            UIManager.showNotification('Pendaftaran berhasil!', 'success');
            UIManager.hideModal('registerModal');
            this.updateNavigation();
            return response.data;
        } catch (error) {
            UIManager.showNotification(error.message, 'error');
            throw error;
        }
    }

    static async login(email, password) {
        try {
            const response = await ApiClient.post(API_ENDPOINTS.AUTH.LOGIN, {
                email, password
            });
            Storage.setToken(response.data.token);
            UIManager.showNotification('Login berhasil!', 'success');
            UIManager.hideModal('loginModal');
            this.updateNavigation();
            return response.data;
        } catch (error) {
            UIManager.showNotification(error.message, 'error');
            throw error;
        }
    }

    static logout() {
        Storage.removeToken();
        UIManager.showNotification('Logout berhasil!', 'success');
        this.updateNavigation();
    }

    static updateNavigation() {
        const navMenu = document.getElementById('navMenu');
        const loginBtn = document.getElementById('loginBtn');

        if (Storage.isAuthenticated()) {
            loginBtn.textContent = 'Logout';
            loginBtn.onclick = () => this.logout();
        } else {
            loginBtn.textContent = 'Login';
            loginBtn.onclick = () => UIManager.showModal('loginModal');
        }
    }
}

// Event Listeners
document.addEventListener('DOMContentLoaded', async () => {
    // Initialize
    AuthManager.updateNavigation();
    await DataManager.loadStatistics();
    const stations = await DataManager.loadStations();
    await DataManager.displayStations(stations);

    // Modal controls
    document.getElementById('loginBtn').addEventListener('click', () => {
        if (!Storage.isAuthenticated()) {
            UIManager.showModal('loginModal');
        }
    });

    document.getElementById('closeLoginModal').addEventListener('click', () => {
        UIManager.hideModal('loginModal');
    });

    document.getElementById('closeRegisterModal').addEventListener('click', () => {
        UIManager.hideModal('registerModal');
    });

    document.getElementById('showRegister').addEventListener('click', (e) => {
        e.preventDefault();
        UIManager.hideModal('loginModal');
        UIManager.showModal('registerModal');
    });

    document.getElementById('showLogin').addEventListener('click', (e) => {
        e.preventDefault();
        UIManager.hideModal('registerModal');
        UIManager.showModal('loginModal');
    });

    // Login form
    document.getElementById('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const email = document.getElementById('loginEmail').value;
        const password = document.getElementById('loginPassword').value;

        try {
            await AuthManager.login(email, password);
            document.getElementById('loginForm').reset();
        } catch (error) {
            // Error handled in AuthManager
        }
    });

    // Register form
    document.getElementById('registerForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = document.getElementById('registerName').value;
        const email = document.getElementById('registerEmail').value;
        const password = document.getElementById('registerPassword').value;

        try {
            await AuthManager.register(name, email, password);
            document.getElementById('registerForm').reset();
        } catch (error) {
            // Error handled in AuthManager
        }
    });

    // Start button
    document.getElementById('startBtn').addEventListener('click', () => {
        if (Storage.isAuthenticated()) {
            alert('Buka halaman dashboard untuk monitoring lebih lanjut');
        } else {
            UIManager.showModal('loginModal');
        }
    });

    // Refresh data every 5 minutes
    setInterval(async () => {
        const stations = await DataManager.loadStations();
        await DataManager.displayStations(stations);
        await DataManager.loadStatistics();
    }, 5 * 60 * 1000);
});
