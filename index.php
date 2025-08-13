<?php
require_once 'config/database.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

Auth::init();
$pageTitle = 'Home - StreamFlix Pro';

// Initialize sample data if needed
if (!file_exists(DATA_PATH . 'users.json')) {
    require_once 'data/sample_data.php';
}

$storage = getStorage();

// Get current user profile
$profile = Auth::getCurrentProfile();
$profileId = $profile['id'];

// Get featured content from file storage
$featuredTitle = null;
if ($profileId) {
    $titles = $storage->read('titles');
    $featuredTitles = array_filter($titles, function($t) {
        return $t['is_featured'] && $t['status'] === 'active';
    });
    if (!empty($featuredTitles)) {
        $featuredTitle = $featuredTitles[array_rand($featuredTitles)];
    }
}

// Get continue watching
$continueWatching = [];

// Get trending content
$titles = $storage->read('titles');
$trendingTitles = array_filter($titles, function($t) {
    return $t['status'] === 'active';
});
$trendingTitles = array_slice($trendingTitles, 0, 12);

// Get live channels
$channels = $storage->read('channels');
$liveChannels = array_filter($channels, function($c) {
    return $c['status'] === 'active';
});
usort($liveChannels, function($a, $b) {
    return $a['sort_order'] <=> $b['sort_order'];
});
$liveChannels = array_slice($liveChannels, 0, 12);

// Get recent movies
$recentMovies = array_filter($titles, function($t) {
    return $t['type'] === 'movie' && $t['status'] === 'active';
});
$recentMovies = array_slice($recentMovies, 0, 12);

// Get popular series
$popularSeries = array_filter($titles, function($t) {
    return $t['type'] === 'series' && $t['status'] === 'active';
});
$popularSeries = array_slice($popularSeries, 0, 12);

include 'includes/header.php';
?>

<!-- Hero Section -->
<?php if ($featuredTitle): ?>
<section class="hero-section" style="background-image: url('<?= getBackdropUrl($featuredTitle['backdrop']) ?>')">
    <div class="hero-overlay"></div>
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title"><?= htmlspecialchars($featuredTitle['name']) ?></h1>
            <?php if ($featuredTitle['description']): ?>
            <p class="hero-description"><?= htmlspecialchars(substr($featuredTitle['description'], 0, 200)) ?>...</p>
            <?php endif; ?>
            <div class="hero-actions">
                <a href="/watch.php?title=<?= $featuredTitle['id'] ?>" class="btn btn-play btn-lg me-3">
                    <i class="bi bi-play-fill me-2"></i>Play Now
                </a>
                <a href="/title.php?id=<?= $featuredTitle['id'] ?>" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-info-circle me-2"></i>More Info
                </a>
            </div>
            <div class="hero-meta mt-3">
                <span class="badge badge-<?= $featuredTitle['type'] === 'movie' ? 'premium' : 'new' ?> me-2">
                    <?= ucfirst($featuredTitle['type']) ?>
                </span>
                <?php if ($featuredTitle['year']): ?>
                <span class="text-light me-3"><?= $featuredTitle['year'] ?></span>
                <?php endif; ?>
                <?php if ($featuredTitle['imdb_rating']): ?>
                <span class="text-warning">
                    <i class="bi bi-star-fill me-1"></i><?= $featuredTitle['imdb_rating'] ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<div class="container my-5">
    <!-- Continue Watching -->
    <?php if (!empty($continueWatching)): ?>
    <section class="content-section">
        <h2 class="section-title">
            <i class="bi bi-play-circle me-2"></i>Continue Watching
        </h2>
        <div class="horizontal-scroll">
            <div class="row">
                <?php foreach ($continueWatching as $item): ?>
                <div class="col">
                    <div class="content-card" data-title-id="<?= $item['title_id'] ?>" data-episode-id="<?= $item['episode_id'] ?>">
                        <div class="position-relative">
                            <img src="<?= getPosterUrl($item['poster']) ?>" 
                                 class="content-card-img" 
                                 alt="<?= htmlspecialchars($item['display_name']) ?>">
                            
                            <!-- Progress Bar -->
                            <?php 
                            $progress = $item['duration_seconds'] > 0 ? 
                                       ($item['position_seconds'] / $item['duration_seconds']) * 100 : 0;
                            ?>
                            <div class="position-absolute bottom-0 start-0 w-100" style="height: 4px; background: rgba(255,255,255,0.3);">
                                <div style="height: 100%; width: <?= $progress ?>%; background: var(--primary-color);"></div>
                            </div>
                            
                            <div class="content-card-overlay">
                                <div>
                                    <h6 class="text-white mb-1"><?= htmlspecialchars($item['display_name']) ?></h6>
                                    <?php if ($item['season_number']): ?>
                                    <small class="text-light">S<?= $item['season_number'] ?>E<?= $item['episode_number'] ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Live TV -->
    <?php if (!empty($liveChannels)): ?>
    <section class="content-section">
        <h2 class="section-title">
            <i class="bi bi-broadcast me-2"></i>Live TV
            <small class="text-muted ms-2">Now Playing</small>
        </h2>
        <div class="horizontal-scroll">
            <div class="row">
                <?php foreach ($liveChannels as $channel): ?>
                <div class="col">
                    <div class="channel-card" data-channel-id="<?= $channel['id'] ?>" onclick="location.href='/watch.php?channel=<?= $channel['id'] ?>'">
                        <img src="<?= $channel['logo'] ?: '/assets/images/channel-placeholder.png' ?>" 
                             class="channel-logo" 
                             alt="<?= htmlspecialchars($channel['name']) ?>">
                        <div class="channel-name"><?= htmlspecialchars($channel['name']) ?></div>
                        <div class="channel-category"><?= htmlspecialchars($channel['category']) ?></div>
                        <span class="badge badge-live position-absolute top-0 end-0 m-2">LIVE</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="text-center mt-3">
            <a href="/live.php" class="btn btn-outline-primary">View All Channels</a>
        </div>
    </section>
    <?php endif; ?>

    <!-- Trending Now -->
    <?php if (!empty($trendingTitles)): ?>
    <section class="content-section">
        <h2 class="section-title">
            <i class="bi bi-fire me-2"></i>Trending Now
        </h2>
        <div class="horizontal-scroll">
            <div class="row">
                <?php foreach ($trendingTitles as $title): ?>
                <div class="col">
                    <div class="content-card" data-title-id="<?= $title['id'] ?>">
                        <div class="position-relative">
                            <img src="<?= getPosterUrl($title['poster']) ?>" 
                                 class="content-card-img" 
                                 alt="<?= htmlspecialchars($title['name']) ?>">
                            
                            <button class="favorite-btn" data-title-id="<?= $title['id'] ?>">
                                <i class="bi bi-heart"></i>
                            </button>
                            
                            <div class="content-card-overlay">
                                <div>
                                    <h6 class="text-white mb-1"><?= htmlspecialchars($title['name']) ?></h6>
                                    <div class="d-flex align-items-center text-light">
                                        <span class="badge badge-<?= $title['type'] === 'movie' ? 'premium' : 'new' ?> me-2">
                                            <?= ucfirst($title['type']) ?>
                                        </span>
                                        <?php if ($title['year']): ?>
                                        <span class="me-2"><?= $title['year'] ?></span>
                                        <?php endif; ?>
                                        <?php if ($title['imdb_rating']): ?>
                                        <span class="text-warning">
                                            <i class="bi bi-star-fill me-1"></i><?= $title['imdb_rating'] ?>
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
        </div>
    </section>
    <?php endif; ?>

    <!-- Recent Movies -->
    <?php if (!empty($recentMovies)): ?>
    <section class="content-section">
        <h2 class="section-title">
            <i class="bi bi-film me-2"></i>Latest Movies
        </h2>
        <div class="horizontal-scroll">
            <div class="row">
                <?php foreach ($recentMovies as $movie): ?>
                <div class="col">
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
                                        <?php if ($movie['duration']): ?>
                                        <span><?= formatDuration($movie['duration']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="text-center mt-3">
            <a href="/movies.php" class="btn btn-outline-primary">Browse All Movies</a>
        </div>
    </section>
    <?php endif; ?>

    <!-- Popular Series -->
    <?php if (!empty($popularSeries)): ?>
    <section class="content-section">
        <h2 class="section-title">
            <i class="bi bi-tv me-2"></i>Popular Series
        </h2>
        <div class="horizontal-scroll">
            <div class="row">
                <?php foreach ($popularSeries as $series): ?>
                <div class="col">
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
        </div>
        <div class="text-center mt-3">
            <a href="/series.php" class="btn btn-outline-primary">Browse All Series</a>
        </div>
    </section>
    <?php endif; ?>
</div>

<!-- Welcome Modal for First-time Users -->
<?php if (!$profileId): ?>
<div class="modal fade" id="welcomeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Welcome to StreamFlix Pro!</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <i class="bi bi-play-circle-fill text-primary" style="font-size: 4rem;"></i>
                </div>
                <h4 class="text-center mb-3">Get Started in 3 Easy Steps</h4>
                <div class="row">
                    <div class="col-md-4 text-center mb-3">
                        <div class="bg-secondary rounded p-3">
                            <i class="bi bi-gear-fill text-primary fs-1 mb-2"></i>
                            <h6>1. Setup IPTV</h6>
                            <p class="text-muted small">Add your M3U playlist or Xtream Codes details</p>
                        </div>
                    </div>
                    <div class="col-md-4 text-center mb-3">
                        <div class="bg-secondary rounded p-3">
                            <i class="bi bi-person-fill text-primary fs-1 mb-2"></i>
                            <h6>2. Create Profiles</h6>
                            <p class="text-muted small">Set up profiles for family members</p>
                        </div>
                    </div>
                    <div class="col-md-4 text-center mb-3">
                        <div class="bg-secondary rounded p-3">
                            <i class="bi bi-play-fill text-primary fs-1 mb-2"></i>
                            <h6>3. Start Watching</h6>
                            <p class="text-muted small">Enjoy unlimited streaming on all devices</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <a href="/provider.php" class="btn btn-primary">Setup IPTV Now</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Maybe Later</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('welcomeModal'));
    modal.show();
});
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>