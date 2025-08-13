<?php
require_once 'config/database.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

Auth::init();
Auth::requireLogin();

$storage = getStorage();
$pageTitle = 'Live TV - StreamFlix Pro';

// Get all channels
$channels = $storage->read('channels');
$activeChannels = array_filter($channels, function($c) {
    return $c['status'] === 'active';
});

// Group channels by category
$channelsByCategory = [];
foreach ($activeChannels as $channel) {
    $category = $channel['category'] ?: 'General';
    if (!isset($channelsByCategory[$category])) {
        $channelsByCategory[$category] = [];
    }
    $channelsByCategory[$category][] = $channel;
}

// Sort categories and channels
ksort($channelsByCategory);
foreach ($channelsByCategory as &$categoryChannels) {
    usort($categoryChannels, function($a, $b) {
        return $a['sort_order'] <=> $b['sort_order'] ?: $a['name'] <=> $b['name'];
    });
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <h1 class="section-title mb-4">
                <i class="bi bi-broadcast me-2"></i>Live TV Channels
                <span class="badge bg-primary ms-2"><?= count($activeChannels) ?> channels</span>
            </h1>
        </div>
    </div>
    
    <?php if (empty($activeChannels)): ?>
    <div class="row">
        <div class="col-12">
            <div class="text-center py-5">
                <i class="bi bi-broadcast text-muted" style="font-size: 4rem;"></i>
                <h3 class="mt-3 mb-2">No Channels Available</h3>
                <p class="text-muted mb-4">Add an IPTV provider to start watching live TV.</p>
                <a href="/provider.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-plus-circle me-2"></i>Add IPTV Provider
                </a>
            </div>
        </div>
    </div>
    <?php else: ?>
    
    <!-- Channel Categories -->
    <?php foreach ($channelsByCategory as $category => $categoryChannels): ?>
    <section class="content-section mb-5">
        <h2 class="section-title">
            <i class="bi bi-collection me-2"></i><?= htmlspecialchars($category) ?>
            <small class="text-muted ms-2"><?= count($categoryChannels) ?> channels</small>
        </h2>
        
        <div class="row">
            <?php foreach ($categoryChannels as $channel): ?>
            <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-4">
                <div class="channel-card" data-channel-id="<?= $channel['id'] ?>" 
                     onclick="location.href='/watch.php?channel=<?= $channel['id'] ?>'">
                    <img src="<?= $channel['logo'] ?: 'https://images.pexels.com/photos/1279813/pexels-photo-1279813.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&fit=crop' ?>" 
                         class="channel-logo" 
                         alt="<?= htmlspecialchars($channel['name']) ?>"
                         onerror="this.src='https://images.pexels.com/photos/1279813/pexels-photo-1279813.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&fit=crop'">
                    <div class="channel-name"><?= htmlspecialchars($channel['name']) ?></div>
                    <div class="channel-category"><?= htmlspecialchars($channel['category']) ?></div>
                    <span class="badge badge-live position-absolute top-0 end-0 m-2">LIVE</span>
                    
                    <?php if ($channel['country']): ?>
                    <div class="position-absolute top-0 start-0 m-2">
                        <span class="badge bg-secondary"><?= strtoupper($channel['country']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
    
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>