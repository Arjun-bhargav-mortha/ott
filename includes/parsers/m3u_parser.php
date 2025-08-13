<?php
/**
 * M3U Playlist Parser
 */

class M3UParser {
    private $url;
    private $content;
    private $channels = [];
    private $errors = [];
    
    public function __construct($url) {
        $this->url = $url;
    }
    
    public function parse() {
        try {
            $this->fetchContent();
            $this->parseChannels();
            return ['success' => true, 'channels' => $this->channels, 'errors' => $this->errors];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function fetchContent() {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'StreamFlix Pro/1.0',
                'follow_location' => true
            ]
        ]);
        
        $this->content = @file_get_contents($this->url, false, $context);
        
        if ($this->content === false) {
            throw new Exception('Failed to fetch M3U playlist from URL');
        }
        
        if (empty(trim($this->content))) {
            throw new Exception('M3U playlist is empty');
        }
        
        // Check if it's a valid M3U file
        if (strpos($this->content, '#EXTM3U') === false) {
            throw new Exception('Invalid M3U format - missing #EXTM3U header');
        }
    }
    
    private function parseChannels() {
        $lines = explode("\n", $this->content);
        $currentChannel = null;
        $lineNumber = 0;
        
        foreach ($lines as $line) {
            $lineNumber++;
            $line = trim($line);
            
            if (empty($line)) {
                continue;
            }
            
            if (strpos($line, '#EXTINF:') === 0) {
                // Parse channel info
                $currentChannel = $this->parseExtinf($line, $lineNumber);
            } elseif (!empty($line) && !str_starts_with($line, '#')) {
                // This is a stream URL
                if ($currentChannel) {
                    $currentChannel['stream_url'] = $line;
                    
                    // Validate stream URL
                    if ($this->isValidStreamUrl($line)) {
                        $this->channels[] = $currentChannel;
                    } else {
                        $this->errors[] = "Invalid stream URL at line {$lineNumber}: {$line}";
                    }
                    
                    $currentChannel = null;
                } else {
                    $this->errors[] = "Stream URL without channel info at line {$lineNumber}: {$line}";
                }
            }
        }
    }
    
    private function parseExtinf($line, $lineNumber) {
        // Remove #EXTINF: prefix
        $info = substr($line, 8);
        
        // Split by comma to get duration and title
        $parts = explode(',', $info, 2);
        
        if (count($parts) < 2) {
            $this->errors[] = "Invalid EXTINF format at line {$lineNumber}";
            return null;
        }
        
        $attributesAndDuration = trim($parts[0]);
        $title = trim($parts[1]);
        
        $channel = [
            'name' => $title,
            'category' => 'General',
            'logo' => null,
            'tvg_id' => null,
            'tvg_name' => null,
            'country' => null,
            'language' => null,
            'is_adult' => false
        ];
        
        // Parse attributes
        $attributes = $this->parseAttributes($attributesAndDuration);
        
        foreach ($attributes as $key => $value) {
            switch (strtolower($key)) {
                case 'tvg-id':
                    $channel['tvg_id'] = $value;
                    break;
                case 'tvg-name':
                    $channel['tvg_name'] = $value;
                    break;
                case 'tvg-logo':
                    $channel['logo'] = $value;
                    break;
                case 'group-title':
                    $channel['category'] = $value;
                    break;
                case 'tvg-country':
                    $channel['country'] = $value;
                    break;
                case 'tvg-language':
                    $channel['language'] = $value;
                    break;
            }
        }
        
        // Check if it's adult content
        $channel['is_adult'] = $this->isAdultContent($channel['name'], $channel['category']);
        
        // Clean up channel name
        $channel['name'] = $this->cleanChannelName($channel['name']);
        
        return $channel;
    }
    
    private function parseAttributes($attributeString) {
        $attributes = [];
        
        // Match key="value" or key=value patterns
        preg_match_all('/([a-zA-Z-]+)=(["\']?)([^"\'\\s]*?)\2(?=\\s|$)/', $attributeString, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $key = $match[1];
            $value = $match[3];
            $attributes[$key] = $value;
        }
        
        return $attributes;
    }
    
    private function isValidStreamUrl($url) {
        // Basic URL validation
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        // Check for common streaming protocols
        $validProtocols = ['http://', 'https://', 'rtmp://', 'rtsp://'];
        foreach ($validProtocols as $protocol) {
            if (strpos($url, $protocol) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    private function isAdultContent($name, $category) {
        $adultKeywords = ['xxx', 'adult', 'porn', 'sex', 'erotic', 'playboy', 'penthouse'];
        $searchText = strtolower($name . ' ' . $category);
        
        foreach ($adultKeywords as $keyword) {
            if (strpos($searchText, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private function cleanChannelName($name) {
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
            $name = preg_replace($pattern, '', $name);
        }
        
        return trim($name);
    }
    
    public function getChannels() {
        return $this->channels;
    }
    
    public function getErrors() {
        return $this->errors;
    }
    
    public function getStats() {
        return [
            'total_channels' => count($this->channels),
            'categories' => array_unique(array_column($this->channels, 'category')),
            'adult_channels' => count(array_filter($this->channels, fn($c) => $c['is_adult'])),
            'errors' => count($this->errors)
        ];
    }
}