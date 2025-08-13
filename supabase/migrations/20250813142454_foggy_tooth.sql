-- OTT Streaming Platform Database Structure
-- MySQL 8+ Compatible

CREATE DATABASE IF NOT EXISTS ott_streaming CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ott_streaming;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- User profiles (Netflix-style multiple profiles)
CREATE TABLE profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    avatar VARCHAR(255) DEFAULT 'default-avatar.png',
    maturity_rating ENUM('all', 'teen', 'adult') DEFAULT 'all',
    language VARCHAR(10) DEFAULT 'en',
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_profiles (user_id)
);

-- IPTV Providers
CREATE TABLE providers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('m3u', 'xtream') NOT NULL,
    url TEXT NOT NULL,
    username VARCHAR(255) DEFAULT NULL,
    password_encrypted TEXT DEFAULT NULL,
    epg_url TEXT DEFAULT NULL,
    last_sync TIMESTAMP NULL,
    status ENUM('active', 'inactive', 'error') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_providers (user_id)
);

-- Live TV Channels
CREATE TABLE channels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) DEFAULT 'General',
    logo VARCHAR(500) DEFAULT NULL,
    stream_url TEXT NOT NULL,
    tvg_id VARCHAR(100) DEFAULT NULL,
    tvg_name VARCHAR(255) DEFAULT NULL,
    country VARCHAR(10) DEFAULT NULL,
    language VARCHAR(10) DEFAULT NULL,
    is_adult BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE,
    INDEX idx_provider_channels (provider_id),
    INDEX idx_category (category),
    INDEX idx_tvg_id (tvg_id)
);

-- Electronic Program Guide (EPG)
CREATE TABLE epg (
    id INT AUTO_INCREMENT PRIMARY KEY,
    channel_id INT NOT NULL,
    tvg_id VARCHAR(100) DEFAULT NULL,
    title VARCHAR(500) NOT NULL,
    description TEXT,
    category VARCHAR(100) DEFAULT NULL,
    start_time TIMESTAMP NOT NULL,
    end_time TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
    INDEX idx_channel_time (channel_id, start_time, end_time),
    INDEX idx_tvg_time (tvg_id, start_time, end_time),
    INDEX idx_time_range (start_time, end_time)
);

-- Movies and Series (VOD Content)
CREATE TABLE titles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT NOT NULL,
    type ENUM('movie', 'series') NOT NULL,
    name VARCHAR(500) NOT NULL,
    year INT DEFAULT NULL,
    genres JSON DEFAULT NULL,
    poster VARCHAR(500) DEFAULT NULL,
    backdrop VARCHAR(500) DEFAULT NULL,
    description TEXT,
    duration INT DEFAULT NULL, -- in minutes for movies
    imdb_rating DECIMAL(3,1) DEFAULT NULL,
    content_rating VARCHAR(10) DEFAULT NULL,
    country VARCHAR(100) DEFAULT NULL,
    language VARCHAR(10) DEFAULT NULL,
    director VARCHAR(255) DEFAULT NULL,
    cast JSON DEFAULT NULL,
    trailer_url VARCHAR(500) DEFAULT NULL,
    stream_url TEXT DEFAULT NULL, -- for movies
    is_featured BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE,
    INDEX idx_provider_titles (provider_id),
    INDEX idx_type (type),
    INDEX idx_year (year),
    INDEX idx_featured (is_featured),
    FULLTEXT idx_search (name, description)
);

-- TV Series Seasons
CREATE TABLE seasons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title_id INT NOT NULL,
    season_number INT NOT NULL,
    name VARCHAR(255) DEFAULT NULL,
    description TEXT,
    poster VARCHAR(500) DEFAULT NULL,
    episode_count INT DEFAULT 0,
    year INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (title_id) REFERENCES titles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_season (title_id, season_number),
    INDEX idx_title_seasons (title_id)
);

-- TV Series Episodes
CREATE TABLE episodes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    season_id INT NOT NULL,
    episode_number INT NOT NULL,
    name VARCHAR(500) NOT NULL,
    description TEXT,
    duration INT DEFAULT NULL, -- in minutes
    thumbnail VARCHAR(500) DEFAULT NULL,
    stream_url TEXT NOT NULL,
    air_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE,
    UNIQUE KEY unique_episode (season_id, episode_number),
    INDEX idx_season_episodes (season_id)
);

-- User Playback Progress
CREATE TABLE playbacks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profile_id INT NOT NULL,
    title_id INT DEFAULT NULL,
    episode_id INT DEFAULT NULL,
    channel_id INT DEFAULT NULL,
    position_seconds INT DEFAULT 0,
    duration_seconds INT DEFAULT 0,
    completed BOOLEAN DEFAULT FALSE,
    last_watched TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (title_id) REFERENCES titles(id) ON DELETE CASCADE,
    FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE,
    FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
    UNIQUE KEY unique_playback (profile_id, title_id, episode_id, channel_id),
    INDEX idx_profile_playbacks (profile_id),
    INDEX idx_last_watched (last_watched)
);

-- User Favorites/Watchlist
CREATE TABLE favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profile_id INT NOT NULL,
    title_id INT DEFAULT NULL,
    channel_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (title_id) REFERENCES titles(id) ON DELETE CASCADE,
    FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (profile_id, title_id, channel_id),
    INDEX idx_profile_favorites (profile_id)
);

-- Site Settings
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('site_name', 'StreamFlix Pro', 'Site name displayed in header'),
('site_logo', '/assets/images/logo.png', 'Site logo path'),
('theme_color', '#e50914', 'Primary theme color'),
('enable_registration', '1', 'Allow new user registrations'),
('max_profiles_per_user', '5', 'Maximum profiles per user account'),
('session_timeout', '86400', 'Session timeout in seconds'),
('enable_epg', '1', 'Enable EPG functionality'),
('default_language', 'en', 'Default language code'),
('adult_content_pin', '', 'PIN for adult content (empty = disabled)');

-- Create default admin user (password: admin123)
INSERT INTO users (email, password_hash, first_name, last_name, role) VALUES
('admin@streamflix.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin');

-- Create default profile for admin
INSERT INTO profiles (user_id, name, is_default) VALUES
(1, 'Admin', TRUE);