<?php
require_once 'config/database.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

Auth::init();
Auth::requireLogin();

$storage = getStorage();
$profile = Auth::getCurrentProfile();
$profileId = $profile['id'];
$pageTitle = 'My List - StreamFlix Pro';

// Get user's favorites (mock data for demo)
$favoriteMovies = [];
$favoriteSeries = [];
$favoriteChannels = [];

// In a real implementation, you would fetch from favorites table
// For demo, we'll show some sample favorites
$allTitles = $storage->read('titles');
$allChannels = $storage->read('channels');

// Mock some favorites for demo
$favoriteMovies = array_slice(array_filter($allTitles, function($t) {
    return $t['type'] === 'movie' && $t['status'] === 'active';
}), 0, 6);

$favoriteSeries = array_slice(array_filter($allTitles, function($t) {
    return $t['type'] === 'series' && $t['status'] === 'active';
}), 0, 6);

$favoriteChannels = array_slice(array_filter($allChannels, function($c) {
    return $c['status'] === 'active';
}), 0, 8);

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <h1 class="section-title mb-4">
                <i class="bi bi-heart-fill me-2"></i>My List
                <small class="text-muted ms-2">Your favorite content</small>
            </h1>
        </div>
    </div>
    
    <?php if (empty($favoriteMovies) && empty($favoriteSeries) && empty($favoriteChannels)): ?>
    <div class="row">
        <div class="col-12">
            <div class="text-center py-5">
                <i class="bi bi-heart text-muted" style="font-size: 4rem;"></i>
                <h3 class="mt-3 mb-2">Your List is Empty</h3>
                <p class="text-muted mb-4">Start adding movies, series, and channels to your favorites.</p>
                <div class="d-flex gap-3 justify-content-center">
                    <a href="/movies.php" class="btn btn-primary">
                        <i class="bi bi-film me-2"></i>Browse Movies
                    </a>
                    <a href="/series.php" class="btn btn-outline-primary">
                        <i class="bi bi-tv me-2"></i>Browse Series
                    </a>
                    <a href="/live.php" class="btn btn-outline-primary">
                        <i class="bi bi-broadcast me-2"></i>Live TV
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    
    <!-- Favorite Movies -->
    <?php if (!empty($favoriteMovies)): ?>
    <section class="content-section mb-5">
        <h2 class="section-title">
            <i class="bi bi-film me-2"></i>Favorite Movies
            <small class="text-muted ms-2"><?= count($favoriteMovies) ?> movies</small>
        </h2>
        <div class="row">
            <?php foreach ($favoriteMovies as $movie): ?>
            <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-4">
                <div class="content-card" data-title-id="<?= $movie['id'] ?>">
                    <div class="position-relative">
                        <img src="<?= getPosterUrl($movie['poster']) ?>" 
                             class="content-card-img" 
                             alt="<?= htmlspecialchars($movie['name']) ?>">
                        
                        <button class="favorite-btn active" data-title-id="<?= $movie['id'] ?>">
                            <i class="bi bi-heart-fill"></i>
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
        <div class="text-center">
            <a href="/movies.php" class="btn btn-outline-primary">
                <i class="bi bi-plus-circle me-2"></i>Add More Movies
            </a>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Favorite Series -->
    <?php if (!empty($favoriteSeries)): ?>
    <section class="content-section mb-5">
        <h2 class="section-title">
            <i class="bi bi-tv me-2"></i>Favorite Series
            <small class="text-muted ms-2"><?= count($favoriteSeries) ?> series</small>
        </h2>
        <div class="row">
            <?php foreach ($favoriteSeries as $series): ?>
            <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-4">
                <div class="content-card" data-title-id="<?= $series['id'] ?>">
                    <div class="position-relative">
                        <img src="<?= getPosterUrl($series['poster']) ?>" 
                             class="content-card-img" 
                             alt="<?= htmlspecialchars($series['name']) ?>">
                        
                        <button class="favorite-btn active" data-title-id="<?= $series['id'] ?>">
                            <i class="bi bi-heart-fill"></i>
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
        <div class="text-center">
            <a href="/series.php" class="btn btn-outline-primary">
                <i class="bi bi-plus-circle me-2"></i>Add More Series
            </a>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Favorite Channels -->
    <?php if (!empty($favoriteChannels)): ?>
    <section class="content-section mb-5">
        <h2 class="section-title">
            <i class="bi bi-broadcast me-2"></i>Favorite Channels
            <small class="text-muted ms-2"><?= count($favoriteChannels) ?> channels</small>
        </h2>
        <div class="row">
            <?php foreach ($favoriteChannels as $channel): ?>
            <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-4">
                <div class="channel-card" data-channel-id="<?= $channel['id'] ?>" 
                     onclick="location.href='/watch.php?channel=<?= $channel['id'] ?>'">
                    <img src="<?= $channel['logo'] ?: 'https://images.pexels.com/photos/1279813/pexels-photo-1279813.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&fit=crop' ?>" 
                         class="channel-logo" 
                         alt="<?= htmlspecialchars($channel['name']) ?>">
                    <div class="channel-name"><?= htmlspecialchars($channel['name']) ?></div>
                    <div class="channel-category"><?= htmlspecialchars($channel['category']) ?></div>
                    <span class="badge badge-live position-absolute top-0 end-0 m-2">LIVE</span>
                    
                    <button class="favorite-btn active position-absolute top-0 start-0 m-2" 
                            data-channel-id="<?= $channel['id'] ?>" onclick="event.stopPropagation();">
                        <i class="bi bi-heart-fill"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center">
            <a href="/live.php" class="btn btn-outline-primary">
                <i class="bi bi-plus-circle me-2"></i>Add More Channels
            </a>
        </div>
    </section>
    <?php endif; ?>
    
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>