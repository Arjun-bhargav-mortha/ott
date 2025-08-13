<?php
require_once 'config/database.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

Auth::init();
Auth::requireLogin();

$storage = getStorage();
$query = sanitize($_GET['q'] ?? '');
$pageTitle = !empty($query) ? "Search: $query - StreamFlix Pro" : 'Search - StreamFlix Pro';

$results = [
    'channels' => [],
    'movies' => [],
    'series' => []
];

if (!empty($query) && strlen($query) >= 2) {
    // Search channels
    $allChannels = $storage->read('channels');
    $results['channels'] = array_filter($allChannels, function($c) use ($query) {
        return $c['status'] === 'active' && (
            stripos($c['name'], $query) !== false ||
            stripos($c['category'], $query) !== false
        );
    });
    
    // Search titles (movies and series)
    $allTitles = $storage->read('titles');
    foreach ($allTitles as $title) {
        if ($title['status'] !== 'active') continue;
        
        $matches = stripos($title['name'], $query) !== false ||
                  stripos($title['description'] ?? '', $query) !== false ||
                  (is_array($title['genres']) && array_filter($title['genres'], function($g) use ($query) {
                      return stripos($g, $query) !== false;
                  }));
        
        if ($matches) {
            if ($title['type'] === 'movie') {
                $results['movies'][] = $title;
            } else {
                $results['series'][] = $title;
            }
        }
    }
    
    // Limit results
    $results['channels'] = array_slice($results['channels'], 0, 20);
    $results['movies'] = array_slice($results['movies'], 0, 20);
    $results['series'] = array_slice($results['series'], 0, 20);
}

$totalResults = count($results['channels']) + count($results['movies']) + count($results['series']);

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <?php if (!empty($query)): ?>
            <h1 class="section-title mb-4">
                <i class="bi bi-search me-2"></i>Search Results for "<?= htmlspecialchars($query) ?>"
                <span class="badge bg-primary ms-2"><?= $totalResults ?> results</span>
            </h1>
            <?php else: ?>
            <h1 class="section-title mb-4">
                <i class="bi bi-search me-2"></i>Search
            </h1>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Search Form -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-dark border-secondary">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-8">
                            <input type="text" class="form-control form-control-lg" name="q" 
                                   value="<?= htmlspecialchars($query) ?>" 
                                   placeholder="Search for movies, series, channels..." 
                                   autofocus>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="bi bi-search me-2"></i>Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (empty($query)): ?>
    <!-- Search Suggestions -->
    <div class="row">
        <div class="col-12">
            <div class="text-center py-5">
                <i class="bi bi-search text-muted" style="font-size: 4rem;"></i>
                <h3 class="mt-3 mb-2">Discover Content</h3>
                <p class="text-muted mb-4">Search for your favorite movies, TV series, and live channels.</p>
                
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card bg-secondary h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-film text-primary fs-1 mb-3"></i>
                                <h5>Movies</h5>
                                <p class="text-muted">Find blockbusters, classics, and new releases</p>
                                <a href="/movies.php" class="btn btn-outline-primary">Browse Movies</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-secondary h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-tv text-primary fs-1 mb-3"></i>
                                <h5>TV Series</h5>
                                <p class="text-muted">Discover popular shows and binge-worthy series</p>
                                <a href="/series.php" class="btn btn-outline-primary">Browse Series</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-secondary h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-broadcast text-primary fs-1 mb-3"></i>
                                <h5>Live TV</h5>
                                <p class="text-muted">Watch live channels from around the world</p>
                                <a href="/live.php" class="btn btn-outline-primary">Watch Live</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php elseif ($totalResults === 0): ?>
    <!-- No Results -->
    <div class="row">
        <div class="col-12">
            <div class="text-center py-5">
                <i class="bi bi-search text-muted" style="font-size: 4rem;"></i>
                <h3 class="mt-3 mb-2">No Results Found</h3>
                <p class="text-muted mb-4">
                    We couldn't find anything matching "<?= htmlspecialchars($query) ?>".
                </p>
                <div class="mb-4">
                    <h6>Try:</h6>
                    <ul class="list-unstyled text-muted">
                        <li>• Checking your spelling</li>
                        <li>• Using different keywords</li>
                        <li>• Searching for a specific genre or category</li>
                    </ul>
                </div>
                <a href="/search.php" class="btn btn-primary">
                    <i class="bi bi-arrow-left me-2"></i>New Search
                </a>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Search Results -->
    
    <!-- Live Channels -->
    <?php if (!empty($results['channels'])): ?>
    <section class="content-section mb-5">
        <h2 class="section-title">
            <i class="bi bi-broadcast me-2"></i>Live Channels
            <small class="text-muted ms-2"><?= count($results['channels']) ?> found</small>
        </h2>
        <div class="row">
            <?php foreach ($results['channels'] as $channel): ?>
            <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-4">
                <div class="channel-card" data-channel-id="<?= $channel['id'] ?>" 
                     onclick="location.href='/watch.php?channel=<?= $channel['id'] ?>'">
                    <img src="<?= $channel['logo'] ?: 'https://images.pexels.com/photos/1279813/pexels-photo-1279813.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&fit=crop' ?>" 
                         class="channel-logo" 
                         alt="<?= htmlspecialchars($channel['name']) ?>">
                    <div class="channel-name"><?= htmlspecialchars($channel['name']) ?></div>
                    <div class="channel-category"><?= htmlspecialchars($channel['category']) ?></div>
                    <span class="badge badge-live position-absolute top-0 end-0 m-2">LIVE</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Movies -->
    <?php if (!empty($results['movies'])): ?>
    <section class="content-section mb-5">
        <h2 class="section-title">
            <i class="bi bi-film me-2"></i>Movies
            <small class="text-muted ms-2"><?= count($results['movies']) ?> found</small>
        </h2>
        <div class="row">
            <?php foreach ($results['movies'] as $movie): ?>
            <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-4">
                <div class="content-card" data-title-id="<?= $movie['id'] ?>">
                    <div class="position-relative">
                        <img src="<?= getPosterUrl($movie['poster']) ?>" 
                             class="content-card-img" 
                             alt="<?= htmlspecialchars($movie['name']) ?>">
                        
                        <button class="favorite-btn" data-title-id="<?= $movie['id'] ?>">
                            <i class="bi bi-heart"></i>
                        </button>
                        
                        <div class="content-card-overlay">
                            <div>
                                <h6 class="text-white mb-1"><?= htmlspecialchars($movie['name']) ?></h6>
                                <div class="d-flex align-items-center text-light">
                                    <span class="badge badge-premium me-2">Movie</span>
                                    <?php if ($movie['year']): ?>
                                    <span class="me-2"><?= $movie['year'] ?></span>
                                    <?php endif; ?>
                                    <?php if ($movie['imdb_rating']): ?>
                                    <span class="text-warning">
                                        <i class="bi bi-star-fill me-1"></i><?= $movie['imdb_rating'] ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- TV Series -->
    <?php if (!empty($results['series'])): ?>
    <section class="content-section mb-5">
        <h2 class="section-title">
            <i class="bi bi-tv me-2"></i>TV Series
            <small class="text-muted ms-2"><?= count($results['series']) ?> found</small>
        </h2>
        <div class="row">
            <?php foreach ($results['series'] as $series): ?>
            <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-4">
                <div class="content-card" data-title-id="<?= $series['id'] ?>">
                    <div class="position-relative">
                        <img src="<?= getPosterUrl($series['poster']) ?>" 
                             class="content-card-img" 
                             alt="<?= htmlspecialchars($series['name']) ?>">
                        
                        <button class="favorite-btn" data-title-id="<?= $series['id'] ?>">
                            <i class="bi bi-heart"></i>
                        </button>
                        
                        <div class="content-card-overlay">
                            <div>
                                <h6 class="text-white mb-1"><?= htmlspecialchars($series['name']) ?></h6>
                                <div class="d-flex align-items-center text-light">
                                    <span class="badge badge-new me-2">Series</span>
                                    <?php if ($series['year']): ?>
                                    <span class="me-2"><?= $series['year'] ?></span>
                                    <?php endif; ?>
                                    <?php if ($series['imdb_rating']): ?>
                                    <span class="text-warning">
                                        <i class="bi bi-star-fill me-1"></i><?= $series['imdb_rating'] ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>