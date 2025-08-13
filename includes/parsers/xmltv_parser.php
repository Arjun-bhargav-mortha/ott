<?php
/**
 * XMLTV EPG Parser
 */

class XMLTVParser {
    private $url;
    private $xml;
    private $epgData = [];
    private $errors = [];
    
    public function __construct($url) {
        $this->url = $url;
    }
    
    public function parse() {
        try {
            $this->fetchContent();
            $this->parseEPG();
            return ['success' => true, 'epg_data' => $this->epgData, 'errors' => $this->errors];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function fetchContent() {
        $context = stream_context_create([
            'http' => [
                'timeout' => 60, // XMLTV files can be large
                'user_agent' => 'StreamFlix Pro/1.0',
                'follow_location' => true
            ]
        ]);
        
        $content = @file_get_contents($this->url, false, $context);
        
        if ($content === false) {
            throw new Exception('Failed to fetch XMLTV EPG from URL');
        }
        
        if (empty(trim($content))) {
            throw new Exception('XMLTV EPG is empty');
        }
        
        // Parse XML
        libxml_use_internal_errors(true);
        $this->xml = simplexml_load_string($content);
        
        if ($this->xml === false) {
            $errors = libxml_get_errors();
            $errorMsg = 'Invalid XML format';
            if (!empty($errors)) {
                $errorMsg .= ': ' . $errors[0]->message;
            }
            throw new Exception($errorMsg);
        }
        
        // Validate XMLTV format
        if ($this->xml->getName() !== 'tv') {
            throw new Exception('Invalid XMLTV format - root element must be <tv>');
        }
    }
    
    private function parseEPG() {
        $programCount = 0;
        
        // Parse programmes
        foreach ($this->xml->programme as $programme) {
            try {
                $epgEntry = $this->parseProgramme($programme);
                if ($epgEntry) {
                    $this->epgData[] = $epgEntry;
                    $programCount++;
                }
            } catch (Exception $e) {
                $this->errors[] = 'Error parsing programme: ' . $e->getMessage();
            }
        }
        
        if ($programCount === 0) {
            throw new Exception('No valid programmes found in XMLTV file');
        }
    }
    
    private function parseProgramme($programme) {
        $attributes = $programme->attributes();
        
        if (!isset($attributes['channel'])) {
            $this->errors[] = 'Programme missing channel attribute';
            return null;
        }
        
        if (!isset($attributes['start'])) {
            $this->errors[] = 'Programme missing start time';
            return null;
        }
        
        if (!isset($attributes['stop'])) {
            $this->errors[] = 'Programme missing stop time';
            return null;
        }
        
        $channel = (string)$attributes['channel'];
        $startTime = $this->parseXMLTVTime((string)$attributes['start']);
        $endTime = $this->parseXMLTVTime((string)$attributes['stop']);
        
        if (!$startTime || !$endTime) {
            $this->errors[] = 'Invalid time format for programme on channel ' . $channel;
            return null;
        }
        
        // Extract programme details
        $title = '';
        $description = '';
        $category = '';
        
        // Title (required)
        if (isset($programme->title)) {
            $title = (string)$programme->title;
        }
        
        if (empty($title)) {
            $this->errors[] = 'Programme missing title on channel ' . $channel;
            return null;
        }
        
        // Description
        if (isset($programme->desc)) {
            $description = (string)$programme->desc;
        }
        
        // Category
        if (isset($programme->category)) {
            $category = (string)$programme->category;
        }
        
        return [
            'tvg_id' => $channel,
            'title' => $this->cleanText($title),
            'description' => $this->cleanText($description),
            'category' => $this->cleanText($category),
            'start_time' => $startTime,
            'end_time' => $endTime
        ];
    }
    
    private function parseXMLTVTime($timeString) {
        // XMLTV time format: YYYYMMDDHHmmss +ZZZZ
        // Example: 20231201120000 +0000
        
        if (preg_match('/^(\d{14})\s*([\+\-]\d{4})?$/', $timeString, $matches)) {
            $datetime = $matches[1];
            $timezone = isset($matches[2]) ? $matches[2] : '+0000';
            
            // Parse datetime
            $year = substr($datetime, 0, 4);
            $month = substr($datetime, 4, 2);
            $day = substr($datetime, 6, 2);
            $hour = substr($datetime, 8, 2);
            $minute = substr($datetime, 10, 2);
            $second = substr($datetime, 12, 2);
            
            try {
                // Create DateTime object
                $dt = new DateTime();
                $dt->setDate($year, $month, $day);
                $dt->setTime($hour, $minute, $second);
                
                // Apply timezone offset
                if ($timezone !== '+0000') {
                    $offsetHours = (int)substr($timezone, 1, 2);
                    $offsetMinutes = (int)substr($timezone, 3, 2);
                    $offsetSign = substr($timezone, 0, 1);
                    
                    $totalOffsetMinutes = ($offsetHours * 60) + $offsetMinutes;
                    if ($offsetSign === '-') {
                        $totalOffsetMinutes = -$totalOffsetMinutes;
                    }
                    
                    $dt->modify(($offsetSign === '+' ? '-' : '+') . $totalOffsetMinutes . ' minutes');
                }
                
                return $dt->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                return null;
            }
        }
        
        return null;
    }
    
    private function cleanText($text) {
        // Remove extra whitespace and decode HTML entities
        $text = trim($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        return $text;
    }
    
    public function getEPGData() {
        return $this->epgData;
    }
    
    public function getErrors() {
        return $this->errors;
    }
    
    public function getStats() {
        if (empty($this->epgData)) {
            return ['total_programmes' => 0, 'channels' => [], 'date_range' => null];
        }
        
        $channels = array_unique(array_column($this->epgData, 'tvg_id'));
        $startTimes = array_column($this->epgData, 'start_time');
        
        return [
            'total_programmes' => count($this->epgData),
            'channels' => $channels,
            'channel_count' => count($channels),
            'date_range' => [
                'start' => min($startTimes),
                'end' => max($startTimes)
            ]
        ];
    }
    
    /**
     * Filter EPG data by date range
     */
    public function filterByDateRange($startDate, $endDate) {
        return array_filter($this->epgData, function($programme) use ($startDate, $endDate) {
            $progDate = substr($programme['start_time'], 0, 10);
            return $progDate >= $startDate && $progDate <= $endDate;
        });
    }
    
    /**
     * Get EPG data for specific channels
     */
    public function filterByChannels($channelIds) {
        return array_filter($this->epgData, function($programme) use ($channelIds) {
            return in_array($programme['tvg_id'], $channelIds);
        });
    }
}