<?php
/**
 * Sample Data Generator for Demo
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/db.php';

$storage = getStorage();

// Create sample users
$users = [
    [
        'id' => 1,
        'email' => 'admin@streamflix.com',
        'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
        'first_name' => 'Admin',
        'last_name' => 'User',
        'role' => 'admin',
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ],
    [
        'id' => 2,
        'email' => 'demo@streamflix.com',
        'password_hash' => password_hash('demo123', PASSWORD_DEFAULT),
        'first_name' => 'Demo',
        'last_name' => 'User',
        'role' => 'user',
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]
];

$storage->write('users', $users);

// Create sample profiles
$profiles = [
    [
        'id' => 1,
        'user_id' => 1,
        'name' => 'Admin',
        'avatar' => 'default-avatar.png',
        'maturity_rating' => 'all',
        'language' => 'en',
        'is_default' => true,
        'created_at' => date('Y-m-d H:i:s')
    ],
    [
        'id' => 2,
        'user_id' => 2,
        'name' => 'Demo',
        'avatar' => 'default-avatar.png',
        'maturity_rating' => 'all',
        'language' => 'en',
        'is_default' => true,
        'created_at' => date('Y-m-d H:i:s')
    ]
];

$storage->write('profiles', $profiles);

// Create sample providers
$providers = [
    [
        'id' => 1,
        'user_id' => 1,
        'name' => 'Premium Plus TV',
        'type' => 'xtream',
        'url' => 'http://tv.premiumplus.tv:80',
        'username' => 'c',
        'password_encrypted' => encryptData('00:1a:79:38:d1:00'),
        'epg_url' => null,
        'last_sync' => date('Y-m-d H:i:s'),
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s')
    ],
    [
        'id' => 2,
        'user_id' => 1,
        'name' => 'Gogo8K IPTV',
        'type' => 'm3u',
        'url' => 'http://ffg.gogo8k.me/playlist.m3u8',
        'username' => null,
        'password_encrypted' => null,
        'epg_url' => null,
        'last_sync' => date('Y-m-d H:i:s'),
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s')
    ]
];

$storage->write('providers', $providers);

// Create sample channels
$channels = [
    [
        'id' => 1,
        'provider_id' => 1,
        'name' => 'CNN International',
        'category' => 'News',
        'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b1/CNN.svg/320px-CNN.svg.png',
        'stream_url' => 'http://tv.premiumplus.tv:80/live/c/00:1a:79:38:d1:00/1.ts',
        'tvg_id' => 'cnn',
        'tvg_name' => 'CNN',
        'country' => 'US',
        'language' => 'en',
        'is_adult' => false,
        'sort_order' => 1,
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s')
    ],
    [
        'id' => 2,
        'provider_id' => 1,
        'name' => 'BBC One HD',
        'category' => 'Entertainment',
        'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8b/BBC_One_logo_%282021%29.svg/320px-BBC_One_logo_%282021%29.svg.png',
        'stream_url' => 'http://tv.premiumplus.tv:80/live/c/00:1a:79:38:d1:00/2.ts',
        'tvg_id' => 'bbc1',
        'tvg_name' => 'BBC One',
        'country' => 'UK',
        'language' => 'en',
        'is_adult' => false,
        'sort_order' => 2,
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s')
    ],
    [
        'id' => 3,
        'provider_id' => 1,
        'name' => 'ESPN HD',
        'category' => 'Sports',
        'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2f/ESPN_wordmark.svg/320px-ESPN_wordmark.svg.png',
        'stream_url' => 'http://tv.premiumplus.tv:80/live/c/00:1a:79:38:d1:00/3.ts',
        'tvg_id' => 'espn',
        'tvg_name' => 'ESPN',
        'country' => 'US',
        'language' => 'en',
        'is_adult' => false,
        'sort_order' => 3,
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s')
    ],
    [
        'id' => 4,
        'provider_id' => 2,
        'name' => 'Discovery Channel',
        'category' => 'Documentary',
        'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/27/Discovery_Channel_-_Logo_2019.svg/320px-Discovery_Channel_-_Logo_2019.svg.png',
        'stream_url' => 'http://ffg.gogo8k.me/discovery.m3u8',
        'tvg_id' => 'discovery',
        'tvg_name' => 'Discovery',
        'country' => 'US',
        'language' => 'en',
        'is_adult' => false,
        'sort_order' => 4,
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s')
    ],
    [
        'id' => 5,
        'provider_id' => 2,
        'name' => 'National Geographic',
        'category' => 'Documentary',
        'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/fc/Natgeologo.svg/320px-Natgeologo.svg.png',
        'stream_url' => 'http://ffg.gogo8k.me/natgeo.m3u8',
        'tvg_id' => 'natgeo',
        'tvg_name' => 'National Geographic',
        'country' => 'US',
        'language' => 'en',
        'is_adult' => false,
        'sort_order' => 5,
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s')
    ]
];

$storage->write('channels', $channels);

// Create sample movies
$movies = [
    [
        'id' => 1,
        'provider_id' => 1,
        'type' => 'movie',
        'name' => 'The Matrix',
        'year' => 1999,
        'genres' => ['Action', 'Sci-Fi'],
        'poster' => 'https://images.pexels.com/photos/7991579/pexels-photo-7991579.jpeg?auto=compress&cs=tinysrgb&w=300&h=450&fit=crop',
        'backdrop' => 'https://images.pexels.com/photos/7991579/pexels-photo-7991579.jpeg?auto=compress&cs=tinysrgb&w=1200&h=675&fit=crop',
        'description' => 'A computer programmer discovers that reality as he knows it is a simulation controlled by machines.',
        'duration' => 136,
        'imdb_rating' => 8.7,
        'content_rating' => 'R',
        'country' => 'US',
        'language' => 'en',
        'director' => 'The Wachowskis',
        'cast' => ['Keanu Reeves', 'Laurence Fishburne', 'Carrie-Anne Moss'],
        'trailer_url' => 'https://example.com/matrix-trailer.mp4',
        'stream_url' => 'https://example.com/matrix.m3u8',
        'is_featured' => true,
        'sort_order' => 1,
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ],
    [
        'id' => 2,
        'provider_id' => 1,
        'type' => 'movie',
        'name' => 'Inception',
        'year' => 2010,
        'genres' => ['Action', 'Thriller', 'Sci-Fi'],
        'poster' => 'https://images.pexels.com/photos/7991225/pexels-photo-7991225.jpeg?auto=compress&cs=tinysrgb&w=300&h=450&fit=crop',
        'backdrop' => 'https://images.pexels.com/photos/7991225/pexels-photo-7991225.jpeg?auto=compress&cs=tinysrgb&w=1200&h=675&fit=crop',
        'description' => 'A thief who steals corporate secrets through dream-sharing technology is given the inverse task of planting an idea.',
        'duration' => 148,
        'imdb_rating' => 8.8,
        'content_rating' => 'PG-13',
        'country' => 'US',
        'language' => 'en',
        'director' => 'Christopher Nolan',
        'cast' => ['Leonardo DiCaprio', 'Marion Cotillard', 'Tom Hardy'],
        'trailer_url' => 'https://example.com/inception-trailer.mp4',
        'stream_url' => 'https://example.com/inception.m3u8',
        'is_featured' => false,
        'sort_order' => 2,
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]
];

$storage->write('titles', $movies);

// Create sample series
$series = [
    [
        'id' => 3,
        'provider_id' => 1,
        'type' => 'series',
        'name' => 'Breaking Bad',
        'year' => 2008,
        'genres' => ['Crime', 'Drama', 'Thriller'],
        'poster' => 'https://images.pexels.com/photos/7991456/pexels-photo-7991456.jpeg?auto=compress&cs=tinysrgb&w=300&h=450&fit=crop',
        'backdrop' => 'https://images.pexels.com/photos/7991456/pexels-photo-7991456.jpeg?auto=compress&cs=tinysrgb&w=1200&h=675&fit=crop',
        'description' => 'A high school chemistry teacher turned methamphetamine manufacturer partners with a former student.',
        'duration' => null,
        'imdb_rating' => 9.5,
        'content_rating' => 'TV-MA',
        'country' => 'US',
        'language' => 'en',
        'director' => 'Vince Gilligan',
        'cast' => ['Bryan Cranston', 'Aaron Paul', 'Anna Gunn'],
        'trailer_url' => 'https://example.com/breaking-bad-trailer.mp4',
        'stream_url' => null,
        'is_featured' => true,
        'sort_order' => 1,
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]
];

// Merge movies and series
$allTitles = array_merge($movies, $series);
$storage->write('titles', $allTitles);

echo "Sample data created successfully!\n";
echo "Demo accounts:\n";
echo "Admin: admin@streamflix.com / admin123\n";
echo "User: demo@streamflix.com / demo123\n";