<?php

namespace App\Config;

class ApiClientAqicn
{
    private $apiKey;
    private $baseUrl = 'https://api.waqi.info';
    private $cacheDir = __DIR__ . '/../../storage/cache';
    private $cacheTtl;

    public function __construct()
    {
        $this->apiKey = $_ENV['AQICN_API_KEY'] ?? null;
        $this->cacheTtl = (int)($_ENV['AQICN_CACHE_TTL'] ?? 1800); // 30 minutes default

        if (!$this->apiKey) {
            throw new \Exception('AQICN_API_KEY not configured in .env');
        }

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Fetch air quality data by coordinates
     * @param float $latitude
     * @param float $longitude
     * @param bool $useCache
     * @return array|false
     */
    public function fetchByCoordinates($latitude, $longitude, $useCache = true)
    {
        $cacheKey = "aqicn_{$latitude}_{$longitude}";
        
        if ($useCache && $this->cacheExists($cacheKey)) {
            return $this->getCache($cacheKey);
        }

        $url = "{$this->baseUrl}/feed/geo:{$latitude};{$longitude}/?token={$this->apiKey}";
        
        $response = $this->makeRequest($url);

        if ($response && isset($response['data'])) {
            $this->setCache($cacheKey, $response['data']);
            return $response['data'];
        }

        return false;
    }

    /**
     * Fetch air quality data by city/station name
     * @param string $city
     * @param bool $useCache
     * @return array|false
     */
    public function fetchByCity($city, $useCache = true)
    {
        $cacheKey = 'aqicn_' . md5($city);
        
        if ($useCache && $this->cacheExists($cacheKey)) {
            return $this->getCache($cacheKey);
        }

        $url = "{$this->baseUrl}/feed/{$city}/?token={$this->apiKey}";
        
        $response = $this->makeRequest($url);

        if ($response && isset($response['data'])) {
            $this->setCache($cacheKey, $response['data']);
            return $response['data'];
        }

        return false;
    }

    /**
     * Fetch historical data for coordinates
     * AQICN API doesn't provide direct historical endpoint for free tier
     * This method attempts to get recent data and can be called multiple times
     * 
     * @param float $latitude
     * @param float $longitude
     * @param string $date Format: YYYY-MM-DD (not supported in free API, for future use)
     * @return array|false
     */
    public function fetchHistoricalByCoordinates($latitude, $longitude, $date = null)
    {
        // Note: Free tier AQICN API doesn't support historical data
        // This returns current data, but the method is here for future expansion
        // For now, we'll just get current data and mark it with the request time
        
        $url = "{$this->baseUrl}/feed/geo:{$latitude};{$longitude}/?token={$this->apiKey}";
        
        $response = $this->makeRequest($url);

        if ($response && isset($response['data'])) {
            return $response['data'];
        }

        return false;
    }

    /**
     * Search for stations near coordinates
     * @param float $latitude
     * @param float $longitude
     * @return array|false
     */
    public function searchStationsNearby($latitude, $longitude)
    {
        // Use the search endpoint to find stations
        $url = "{$this->baseUrl}/search/?token={$this->apiKey}&keyword=geo:{$latitude};{$longitude}";
        
        $response = $this->makeRequest($url);

        if ($response && isset($response['data'])) {
            return $response['data'];
        }

        return false;
    }

    /**
     * Search stations by country or city name
     * @param string $keyword Country or city name (e.g., 'indonesia', 'jakarta')
     * @return array|false
     */
    public function searchStationsByKeyword($keyword)
    {
        $url = "{$this->baseUrl}/search/?token={$this->apiKey}&keyword=" . urlencode($keyword);
        
        $response = $this->makeRequest($url);

        if ($response && isset($response['data'])) {
            return $response['data'];
        }

        return false;
    }

    /**
     * Get all stations in Indonesia
     * This searches for major Indonesian cities
     * @return array Array of station data
     */
    public function getIndonesianStations()
    {
        $cities = [
            // Jawa
            'Jakarta', 'Bekasi', 'Tangerang', 'Depok', 'Bogor', 'Bandung', 
            'Surabaya', 'Semarang', 'Yogyakarta', 'Malang', 'Solo', 'Cirebon',
            // Sumatera
            'Medan', 'Palembang', 'Pekanbaru', 'Padang', 'Jambi', 'Bengkulu',
            'Bandar Lampung', 'Batam',
            // Kalimantan
            'Pontianak', 'Banjarmasin', 'Balikpapan', 'Samarinda', 'Palangkaraya',
            // Sulawesi
            'Makassar', 'Manado', 'Palu', 'Kendari', 'Gorontalo',
            // Bali & Nusa Tenggara
            'Denpasar', 'Mataram', 'Kupang',
            // Maluku & Papua
            'Ambon', 'Ternate', 'Jayapura', 'Sorong'
        ];

        $stations = [];
        
        foreach ($cities as $city) {
            $keyword = $city . ', Indonesia';
            $result = $this->searchStationsByKeyword($keyword);
            
            if ($result && is_array($result)) {
                foreach ($result as $station) {
                    if (isset($station['uid'])) {
                        $stations[] = $station;
                    }
                }
            }
            
            // Rate limiting
            usleep(500000); // 0.5 second delay
        }

        return $stations;
    }

    /**
     * Make HTTP request to API
     * @param string $url
     * @return array|false
     */
    private function makeRequest($url)
    {
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'User-Agent: SIAPKAK/1.0'
                ]
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                return json_decode($response, true);
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if cache file exists and is still valid
     * @param string $key
     * @return bool
     */
    private function cacheExists($key)
    {
        $file = "{$this->cacheDir}/{$key}.json";
        
        if (!file_exists($file)) {
            return false;
        }

        $fileTime = filemtime($file);
        $now = time();

        return ($now - $fileTime) < $this->cacheTtl;
    }

    /**
     * Get data from cache
     * @param string $key
     * @return array|null
     */
    private function getCache($key)
    {
        $file = "{$this->cacheDir}/{$key}.json";
        
        if (file_exists($file)) {
            $content = file_get_contents($file);
            return json_decode($content, true);
        }

        return null;
    }

    /**
     * Store data to cache
     * @param string $key
     * @param mixed $data
     * @return bool
     */
    private function setCache($key, $data)
    {
        $file = "{$this->cacheDir}/{$key}.json";
        return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT)) !== false;
    }

    /**
     * Determine AQI status based on index value
     * @param int $aqi
     * @return string
     */
    public static function getAqiStatus($aqi)
    {
        if ($aqi <= 50) {
            return 'Baik';
        } elseif ($aqi <= 100) {
            return 'Sedang';
        } elseif ($aqi <= 150) {
            return 'Tidak Sehat untuk Kelompok Sensitif';
        } elseif ($aqi <= 200) {
            return 'Tidak Sehat';
        } elseif ($aqi <= 300) {
            return 'Sangat Tidak Sehat';
        } else {
            return 'Berbahaya';
        }
    }

    /**
     * Get AQI color based on status
     * @param string $status
     * @return string hex color code
     */
    public static function getAqiColor($status)
    {
        $colors = [
            'Baik' => '#2ecc71',
            'Sedang' => '#f1c40f',
            'Tidak Sehat untuk Kelompok Sensitif' => '#e67e22',
            'Tidak Sehat' => '#e74c3c',
            'Sangat Tidak Sehat' => '#c0392b',
            'Berbahaya' => '#8b0000'
        ];

        return $colors[$status] ?? '#95a5a6';
    }
}
