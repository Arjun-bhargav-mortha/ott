<?php
/**
 * File-based Data Storage (No Database)
 */

class FileStorage {
    private static $instance = null;
    private $dataPath;
    
    private function __construct() {
        $this->dataPath = DATA_PATH;
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function read($file) {
        $filePath = $this->dataPath . $file . '.json';
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            return json_decode($content, true) ?: [];
        }
        return [];
    }
    
    public function write($file, $data) {
        $filePath = $this->dataPath . $file . '.json';
        return file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    public function append($file, $data) {
        $existing = $this->read($file);
        $existing[] = $data;
        return $this->write($file, $existing);
    }
    
    public function find($file, $callback) {
        $data = $this->read($file);
        return array_filter($data, $callback);
    }
    
    public function findOne($file, $callback) {
        $results = $this->find($file, $callback);
        return !empty($results) ? array_values($results)[0] : null;
    }
    
    public function update($file, $callback, $newData) {
        $data = $this->read($file);
        foreach ($data as $key => $item) {
            if ($callback($item)) {
                $data[$key] = array_merge($item, $newData);
            }
        }
        return $this->write($file, $data);
    }
    
    public function delete($file, $callback) {
        $data = $this->read($file);
        $data = array_filter($data, function($item) use ($callback) {
            return !$callback($item);
        });
        return $this->write($file, array_values($data));
    }
}

// Global storage instance
function getStorage() {
    return FileStorage::getInstance();
}

// Mock database methods for compatibility
function getDB() {
    return new class {
        private $storage;
        
        public function __construct() {
            $this->storage = getStorage();
        }
        
        public function fetch($query, $params = []) {
            // Simple mock - return null for now
            return null;
        }
        
        public function fetchAll($query, $params = []) {
            // Simple mock - return empty array for now
            return [];
        }
        
        public function execute($query, $params = []) {
            // Simple mock - return true
            return true;
        }
        
        public function lastInsertId() {
            return rand(1, 1000);
        }
        
        public function beginTransaction() {
            return true;
        }
        
        public function commit() {
            return true;
        }
        
        public function rollback() {
            return true;
        }
    };
}