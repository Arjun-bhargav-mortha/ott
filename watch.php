<?php
require_once 'config/database.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

Auth::init();
Auth::requireLogin();

$storage = getStorage();
$channelId = intval($_GET['channel'] ?? 0);
$titleId = intval($_GET['title'] ?? 0);

$channel = null;
$title = null;

if ($channelId > 0) {
    $channels = $storage->read('channels');
    foreach ($channels as $c) {
        if ($c['id'] == $channelId) {
            $channel = $c;
            break;
        }
    }
    $pageTitle = $channel ? htmlspecialchars($channel['name']) . ' - StreamFlix Pro' : 'Channel Not Found';
} elseif ($titleId > 0) {
    $titles = $storage->read('titles');
    foreach ($titles as $t) {
        if ($t['id'] == $titleId) {
            $title = $t;
            break;
        }
    }
    $pageTitle = $title ? htmlspecialchars($title['name']) . ' - StreamFlix Pro' : 'Title Not Found';
} else {
    header('Location: /');
    exit;
}

include 'includes/header.php';
?>

<div class="container-fluid p-0">
    <?php if ($channel): ?>
    <!-- Live TV Player -->
    <div class="video-container">
        <video class="video-player" 
               data-src="<?= htmlspecialchars($channel['stream_url']) ?>"
               data-channel-id="<?= $channel['id'] ?>"
               controls 
               autoplay 
               poster="<?= $channel['logo'] ?: 'https://images.pexels.com/photos/1279813/pexels-photo-1279813.jpeg?auto=compress&cs=tinysrgb&w=1200&h=675&fit=crop' ?>">
            <p>Your browser doesn't support HTML5 video. Here is a <a href="<?= htmlspecialchars($channel['stream_url']) ?>">link to the video</a> instead.</p>
        </video>
        
        <div class="video-controls">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="text-white mb-1"><?= htmlspecialchars($channel['name']) ?></h4>
                    <p class="text-light mb-0">
                        <span class="badge badge-live me-2">LIVE</span>
                        <?= htmlspecialchars($channel['category']) ?>
                        <?php if ($channel['country']): ?>
                        • <?= strtoupper($channel['country']) ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div>
                    <button class="btn btn-outline-light btn-sm me-2" onclick="toggleFullscreen()">
                        <i class="bi bi-fullscreen"></i>
                    </button>
                    <button class="btn btn-outline-light btn-sm" onclick="addToFavorites(<?= $channel['id'] ?>, 'channel')">
                        <i class="bi bi-heart"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <?php elseif ($title): ?>
    <!-- Movie/Series Player -->
    <div class="video-container">
        <video class="video-player" 
               data-src="<?= htmlspecialchars($title['stream_url'] ?? '') ?>"
               data-title-id="<?= $title['id'] ?>"
               controls 
               poster="<?= getBackdropUrl($title['backdrop']) ?>">
            <p>Your browser doesn't support HTML5 video.</p>
        </video>
        
        <div class="video-controls">
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
            <div class="d-flex align-items-center justify-content-between mt-3">
                <div>
                    <h4 class="text-white mb-1"><?= htmlspecialchars($title['name']) ?></h4>
                    <p class="text-light mb-0">
                        <span class="badge badge-<?= $title['type'] === 'movie' ? 'premium' : 'new' ?> me-2">
                            <?= ucfirst($title['type']) ?>
                        </span>
                        <?php if ($title['year']): ?>
                        <?= $title['year'] ?>
                        <?php endif; ?>
                        <?php if ($title['duration']): ?>
                        • <?= formatDuration($title['duration']) ?>
                        <?php endif; ?>
                        <?php if ($title['imdb_rating']): ?>
                        • <i class="bi bi-star-fill text-warning"></i> <?= $title['imdb_rating'] ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div>
                    <button class="btn btn-outline-light btn-sm me-2" onclick="toggleFullscreen()">
                        <i class="bi bi-fullscreen"></i>
                    </button>
                    <button class="btn btn-outline-light btn-sm" onclick="addToFavorites(<?= $title['id'] ?>, 'title')">
                        <i class="bi bi-heart"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Content Not Found -->
    <div class="container my-5">
        <div class="text-center py-5">
            <i class="bi bi-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
            <h3 class="mt-3 mb-2">Content Not Found</h3>
            <p class="text-muted mb-4">The requested content could not be found or is no longer available.</p>
            <a href="/" class="btn btn-primary">
                <i class="bi bi-house me-2"></i>Go Home
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function toggleFullscreen() {
    const video = document.querySelector('.video-player');
    if (video.requestFullscreen) {
        video.requestFullscreen();
    } else if (video.webkitRequestFullscreen) {
        video.webkitRequestFullscreen();
    } else if (video.msRequestFullscreen) {
        video.msRequestFullscreen();
    }
}

function addToFavorites(id, type) {
    // Mock function - would normally save to favorites
    alert('Added to favorites! (Demo mode - not actually saved)');
}

// Initialize video player when page loads
document.addEventListener('DOMContentLoaded', function() {
    const video = document.querySelector('.video-player');
    if (video && video.dataset.src) {
        // For demo purposes, we'll just show a message since we don't have real streams
        video.addEventListener('error', function() {
            const container = video.closest('.video-container');
            const errorMsg = document.createElement('div');
            errorMsg.className = 'alert alert-warning text-center position-absolute top-50 start-50 translate-middle';
            errorMsg.style.zIndex = '1000';
            errorMsg.innerHTML = `
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Demo Mode:</strong> This is a demo version. Real IPTV streams would play here with your provider's content.
                <br><small class="text-muted">Stream URL: ${video.dataset.src}</small>
            `;
            container.appendChild(errorMsg);
        });
        
        // Trigger error to show demo message
        video.src = 'invalid-stream-url';
    }
});
</script>

<?php include 'includes/footer.php'; ?>