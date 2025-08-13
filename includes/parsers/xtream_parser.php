<?php
/**
 * Xtream Codes API Parser
 */

class XtreamParser {
    private $serverUrl;
    private $username;
    private $password;
    private $apiUrl;
    private $channels = [];
    private $movies = [];
    private $series = [];
    private $errors = [];
    
    public function __construct($serverUrl, $username, $password) {
        $this->serverUrl = rtrim($serverUrl, '/');
        $this->username = $username;
        $this->password = $password;
        $this->apiUrl = $this->serverUrl . '/player_api.php';
    }
    
    public function parse() {
        try {
            // Test authentication
            if (!$this->authenticate()) {
                return ['success' => false, 'error' => 'Authentication failed'];
            }
            
            // Parse live channels
            $this->parseChannels();
            
            // Parse VOD movies
            $this->parseMovies();
            
            // Parse series
            $this->parseSeries();
            
            return [
                'success' => true,
                'channels' => $this->channels,
                'movies' => $this->movies,
                'series' => $this->series,
                'errors' => $this->errors
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function authenticate() {
        $url = $this->apiUrl . '?' . http_build_query([
            'username' => $this->username,
            'password' => $this->password
        ]);
        
        $response = $this->makeRequest($url);
        
        if (!$response || !isset($response['user_info'])) {
            return false;
        }
        
        return $response['user_info']['status'] === 'Active';
    }
    
    private function parseChannels() {
        $url = $this->apiUrl . '?' . http_build_query([
            'username' => $this->username,
            'password' => $this->password,
            'action' => 'get_live_streams'
        ]);
        
        $response = $this->makeRequest($url);
        
        if (!$response || !is_array($response)) {
            $this->errors[] = 'Failed to fetch live channels';
            return;
        }
        
        foreach ($response as $channel) {
            if (!isset($channel['stream_id']) || !isset($channel['name'])) {
                continue;
            }
            
            $streamUrl = $this->serverUrl . '/live/' . $this->username . '/' . $this->password . '/' . $channel['stream_id'] . '.ts';
            
            $this->channels[] = [
                'name' => $this->cleanText($channel['name']),
                'category' => $this->cleanText($channel['category_name'] ?? 'General'),
                'logo' => $channel['stream_icon'] ?? null,
                'stream_url' => $streamUrl,
                'tvg_id' => $channel['epg_channel_id'] ?? null,
                'tvg_name' => $this->cleanText($channel['name']),
                'country' => null,
                'language' => null,
                'is_adult' => $this->isAdultContent($channel['name'], $channel['category_name'] ?? '')
            ];
        }
    }
    
    private function parseMovies() {
        $url = $this->apiUrl . '?' . http_build_query([
            'username' => $this->username,
            'password' => $this->password,
            'action' => 'get_vod_streams'
        ]);
        
        $response = $this->makeRequest($url);
        
        if (!$response || !is_array($response)) {
            $this->errors[] = 'Failed to fetch VOD movies';
            return;
        }
        
        foreach ($response as $movie) {
            if (!isset($movie['stream_id']) || !isset($movie['name'])) {
                continue;
            }
            
            $streamUrl = $this->serverUrl . '/movie/' . $this->username . '/' . $this->password . '/' . $movie['stream_id'] . '.' . ($movie['container_extension'] ?? 'mp4');
            
            $this->movies[] = [
                'name' => $this->cleanText($movie['name']),
                'year' => $this->extractYear($movie['name']),
                'category' => $this->cleanText($movie['category_name'] ?? 'Movies'),
                'poster' => $movie['stream_icon'] ?? null,
                'description' => $movie['plot'] ?? null,
                'duration' => isset($movie['info']['duration']) ? $this->parseDuration($movie['info']['duration']) : null,
                'rating' => $movie['info']['rating'] ?? null,
                'stream_url' => $streamUrl,
                'is_adult' => $this->isAdultContent($movie['name'], $movie['category_name'] ?? '')
            ];
        }
    }
    
    private function parseSeries() {
        $url = $this->apiUrl . '?' . http_build_query([
            'username' => $this->username,
            'password' => $this->password,
            'action' => 'get_series'
        ]);
        
        $response = $this->makeRequest($url);
        
        if (!$response || !is_array($response)) {
            $this->errors[] = 'Failed to fetch series';
            return;
        }
        
        foreach ($response as $series) {
            if (!isset($series['series_id']) || !isset($series['name'])) {
                continue;
            }
            
            $this->series[] = [
                'name' => $this->cleanText($series['name']),
                'year' => $this->extractYear($series['name']),
                'category' => $this->cleanText($series['category_name'] ?? 'Series'),
                'poster' => $series['cover'] ?? null,
                'description' => $series['plot'] ?? null,
                'rating' => $series['rating'] ?? null,
                'series_id' => $series['series_id'],
                'is_adult' => $this->isAdultContent($series['name'], $series['category_name'] ?? '')
            ];
        }
    }
    
    private function makeRequest($url) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'StreamFlix Pro/1.0',
                'follow_location' => true
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return null;
        }
        
        return json_decode($response, true);
    }
    
    private function cleanText($text) {
        if (empty($text)) {
            return '';
        }
        
        // Remove common prefixes/suffixes
        $patterns = [
            '/^\[.*?\]\s*/',  // Remove [prefix]
            '/\s*\[.*?\]$/',  // Remove [suffix]
            '/^\d+\.\s*/',    // Remove numbering like "1. "
            '/\s*HD$/',       // Remove HD suffix
            '/\s*FHD$/',      // Remove FHD suffix
            '/\s*4K$/',       // Remove 4K suffix
        ];
        
        foreach ($patterns as $pattern) {
            $text = preg_replace($pattern, '', $text);
        }
        
        return trim($text);
    }
    
    private function extractYear($name) {
        if (preg_match('/\((\d{4})\)/', $name, $matches)) {
            return intval($matches[1]);
        }
        if (preg_match('/(\d{4})/', $name, $matches)) {
            $year = intval($matches[1]);
            if ($year >= 1900 && $year <= date('Y') + 2) {
                return $year;
            }
        }
        return null;
    }
    
    private function parseDuration($duration) {
        if (is_numeric($duration)) {
            return intval($duration);
        }
        
        // Parse duration strings like "1h 30m" or "90 min"
        if (preg_match('/(\d+)h\s*(\d+)?m?/', $duration, $matches)) {
            $hours = intval($matches[1]);
            $minutes = isset($matches[2]) ? intval($matches[2]) : 0;
            return ($hours * 60) + $minutes;
        }
        
        if (preg_match('/(\d+)\s*min/', $duration, $matches)) {
            return intval($matches[1]);
        }
        
        return null;
    }
    
    private function isAdultContent($name, $category) {
        $adultKeywords = ['xxx', 'adult', 'porn', 'sex', 'erotic', 'playboy', 'penthouse', '18+'];
        $searchText = strtolower($name . ' ' . $category);
        
        foreach ($adultKeywords as $keyword) {
            if (strpos($searchText, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    public function getChannels() {
        return $this->channels;
    }
    
    public function getMovies() {
        return $this->movies;
    }
    
    public function getSeries() {
        return $this->series;
    }
    
    public function getErrors() {
        return $this->errors;
    }
    
    public function getStats() {
        return [
            'total_channels' => count($this->channels),
            'total_movies' => count($this->movies),
            'total_series' => count($this->series),
            'categories' => array_unique(array_merge(
                array_column($this->channels, 'category'),
                array_column($this->movies, 'category'),
                array_column($this->series, 'category')
            )),
            'adult_channels' => count(array_filter($this->channels, fn($c) => $c['is_adult'])),
            'adult_movies' => count(array_filter($this->movies, fn($m) => $m['is_adult'])),
            'adult_series' => count(array_filter($this->series, fn($s) => $s['is_adult'])),
            'errors' => count($this->errors)
        ];
    }
}