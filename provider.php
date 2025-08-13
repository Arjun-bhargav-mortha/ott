<?php
require_once 'config/database.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/parsers/m3u_parser.php';
require_once 'includes/parsers/xmltv_parser.php';

Auth::init();
Auth::requireLogin();

$db = getDB();
$userId = Auth::getUserId();
$pageTitle = 'IPTV Provider Setup - StreamFlix Pro';

$error = '';
$success = '';

// Handle provider setup
if ($_POST && Auth::validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_provider') {
        $name = sanitize($_POST['name'] ?? '');
        $type = sanitize($_POST['type'] ?? '');
        $url = sanitize($_POST['url'] ?? '');
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $epgUrl = sanitize($_POST['epg_url'] ?? '');
        
        if (empty($name) || empty($type) || empty($url)) {
            $error = 'Please fill in all required fields.';
        } else {
            try {
                $db->beginTransaction();
                
                // Encrypt password if provided
                $encryptedPassword = !empty($password) ? encryptData($password) : null;
                
                // Insert provider
                $providerId = $db->execute(
                    "INSERT INTO providers (user_id, name, type, url, username, password_encrypted, epg_url) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$userId, $name, $type, $url, $username, $encryptedPassword, $epgUrl]
                );
                $providerId = $db->lastInsertId();
                
                // Parse M3U playlist
                if ($type === 'm3u') {
                    $parser = new M3UParser($url);
                    $result = $parser->parse();
                    
                    if ($result['success']) {
                        $channelCount = 0;
                        foreach ($result['channels'] as $channel) {
                            $db->execute(
                                "INSERT INTO channels (provider_id, name, category, logo, stream_url, tvg_id, tvg_name, country, language, is_adult) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                                [
                                    $providerId,
                                    $channel['name'],
                                    $channel['category'],
                                    $channel['logo'],
                                    $channel['stream_url'],
                                    $channel['tvg_id'],
                                    $channel['tvg_name'],
                                    $channel['country'],
                                    $channel['language'],
                                    $channel['is_adult'] ? 1 : 0
                                ]
                            );
                            $channelCount++;
                        }
                        
                        // Parse XMLTV EPG if provided
                        if (!empty($epgUrl)) {
                            $epgParser = new XMLTVParser($epgUrl);
                            $epgResult = $epgParser->parse();
                            
                            if ($epgResult['success']) {
                                foreach ($epgResult['epg_data'] as $epgEntry) {
                                    // Find matching channel
                                    $channel = $db->fetch(
                                        "SELECT id FROM channels WHERE provider_id = ? AND tvg_id = ?",
                                        [$providerId, $epgEntry['tvg_id']]
                                    );
                                    
                                    if ($channel) {
                                        $db->execute(
                                            "INSERT INTO epg (channel_id, tvg_id, title, description, category, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?)",
                                            [
                                                $channel['id'],
                                                $epgEntry['tvg_id'],
                                                $epgEntry['title'],
                                                $epgEntry['description'],
                                                $epgEntry['category'],
                                                $epgEntry['start_time'],
                                                $epgEntry['end_time']
                                            ]
                                        );
                                    }
                                }
                            }
                        }
                        
                        // Update provider status
                        $db->execute(
                            "UPDATE providers SET last_sync = NOW(), status = 'active' WHERE id = ?",
                            [$providerId]
                        );
                        
                        $db->commit();
                        $success = "Provider added successfully! Imported {$channelCount} channels.";
                        
                        logActivity('provider_added', "Provider: $name, Channels: $channelCount");
                    } else {
                        $db->rollback();
                        $error = 'Failed to parse M3U playlist: ' . $result['error'];
                    }
                }
            } catch (Exception $e) {
                $db->rollback();
                $error = 'Failed to add provider: ' . $e->getMessage();
                error_log('Provider setup error: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'delete_provider') {
        $providerId = intval($_POST['provider_id'] ?? 0);
        
        if ($providerId > 0) {
            $provider = $db->fetch(
                "SELECT * FROM providers WHERE id = ? AND user_id = ?",
                [$providerId, $userId]
            );
            
            if ($provider) {
                try {
                    $db->execute("DELETE FROM providers WHERE id = ?", [$providerId]);
                    $success = 'Provider deleted successfully.';
                    logActivity('provider_deleted', $provider['name']);
                } catch (Exception $e) {
                    $error = 'Failed to delete provider: ' . $e->getMessage();
                }
            } else {
                $error = 'Provider not found.';
            }
        }
    } elseif ($action === 'sync_provider') {
        $providerId = intval($_POST['provider_id'] ?? 0);
        
        if ($providerId > 0) {
            $provider = $db->fetch(
                "SELECT * FROM providers WHERE id = ? AND user_id = ?",
                [$providerId, $userId]
            );
            
            if ($provider) {
                try {
                    // Delete existing channels for this provider
                    $db->execute("DELETE FROM channels WHERE provider_id = ?", [$providerId]);
                    
                    // Re-parse M3U playlist
                    $parser = new M3UParser($provider['url']);
                    $result = $parser->parse();
                    
                    if ($result['success']) {
                        $channelCount = 0;
                        foreach ($result['channels'] as $channel) {
                            $db->execute(
                                "INSERT INTO channels (provider_id, name, category, logo, stream_url, tvg_id, tvg_name, country, language, is_adult) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                                [
                                    $providerId,
                                    $channel['name'],
                                    $channel['category'],
                                    $channel['logo'],
                                    $channel['stream_url'],
                                    $channel['tvg_id'],
                                    $channel['tvg_name'],
                                    $channel['country'],
                                    $channel['language'],
                                    $channel['is_adult'] ? 1 : 0
                                ]
                            );
                            $channelCount++;
                        }
                        
                        $db->execute(
                            "UPDATE providers SET last_sync = NOW(), status = 'active' WHERE id = ?",
                            [$providerId]
                        );
                        
                        $success = "Provider synced successfully! Updated {$channelCount} channels.";
                        logActivity('provider_synced', $provider['name']);
                    } else {
                        $error = 'Failed to sync provider: ' . $result['error'];
                    }
                } catch (Exception $e) {
                    $error = 'Failed to sync provider: ' . $e->getMessage();
                    error_log('Provider sync error: ' . $e->getMessage());
                }
            }
        }
    }
}

// Get user's providers
$providers = $db->fetchAll(
    "SELECT p.*, 
            (SELECT COUNT(*) FROM channels WHERE provider_id = p.id) as channel_count
     FROM providers p 
     WHERE p.user_id = ? 
     ORDER BY p.created_at DESC",
    [$userId]
);

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h1 class="section-title mb-2">
                        <i class="bi bi-broadcast me-2"></i>IPTV Provider Setup
                    </h1>
                    <p class="text-muted">Connect your IPTV provider to access live TV, movies, and series.</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProviderModal">
                    <i class="bi bi-plus-circle me-2"></i>Add Provider
                </button>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Provider Cards -->
    <div class="row">
        <?php if (empty($providers)): ?>
        <div class="col-12">
            <div class="text-center py-5">
                <i class="bi bi-broadcast text-muted" style="font-size: 4rem;"></i>
                <h3 class="mt-3 mb-2">No Providers Configured</h3>
                <p class="text-muted mb-4">Add your first IPTV provider to start streaming.</p>
                <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#addProviderModal">
                    <i class="bi bi-plus-circle me-2"></i>Add Your First Provider
                </button>
            </div>
            
            <!-- Setup Guide -->
            <div class="row mt-5">
                <div class="col-md-4">
                    <div class="card bg-dark border-secondary h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-file-earmark-text text-primary fs-1 mb-3"></i>
                            <h5 class="card-title">M3U Playlist</h5>
                            <p class="card-text text-muted">
                                Most common format. Enter your M3U playlist URL provided by your IPTV service.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-dark border-secondary h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-server text-primary fs-1 mb-3"></i>
                            <h5 class="card-title">Xtream Codes API</h5>
                            <p class="card-text text-muted">
                                Advanced format with VOD support. Requires server URL, username, and password.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-dark border-secondary h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-calendar3 text-primary fs-1 mb-3"></i>
                            <h5 class="card-title">XMLTV EPG</h5>
                            <p class="card-text text-muted">
                                Optional electronic program guide for live TV schedules and program information.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <?php foreach ($providers as $provider): ?>
        <div class="col-md-6 col-xl-4 mb-4">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <div>
                            <h5 class="card-title mb-1"><?= htmlspecialchars($provider['name']) ?></h5>
                            <span class="badge badge-<?= $provider['type'] === 'm3u' ? 'primary' : 'new' ?> mb-2">
                                <?= strtoupper($provider['type']) ?>
                            </span>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-link text-muted p-0" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= Auth::generateCSRFToken() ?>">
                                        <input type="hidden" name="action" value="sync_provider">
                                        <input type="hidden" name="provider_id" value="<?= $provider['id'] ?>">
                                        <button type="submit" class="dropdown-item">
                                            <i class="bi bi-arrow-clockwise me-2"></i>Sync Channels
                                        </button>
                                    </form>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this provider? All channels will be removed.')">
                                        <input type="hidden" name="csrf_token" value="<?= Auth::generateCSRFToken() ?>">
                                        <input type="hidden" name="action" value="delete_provider">
                                        <input type="hidden" name="provider_id" value="<?= $provider['id'] ?>">
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="bi bi-trash me-2"></i>Delete
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex align-items-center text-muted mb-2">
                            <i class="bi bi-tv me-2"></i>
                            <span><?= number_format($provider['channel_count']) ?> channels</span>
                        </div>
                        <div class="d-flex align-items-center text-muted mb-2">
                            <i class="bi bi-calendar3 me-2"></i>
                            <span>Added <?= date('M j, Y', strtotime($provider['created_at'])) ?></span>
                        </div>
                        <?php if ($provider['last_sync']): ?>
                        <div class="d-flex align-items-center text-muted mb-2">
                            <i class="bi bi-arrow-clockwise me-2"></i>
                            <span>Last sync: <?= timeAgo($provider['last_sync']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <span class="badge bg-<?= $provider['status'] === 'active' ? 'success' : ($provider['status'] === 'error' ? 'danger' : 'warning') ?>">
                            <?= ucfirst($provider['status']) ?>
                        </span>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="/live.php?provider=<?= $provider['id'] ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-play-circle me-2"></i>View Channels
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add Provider Modal -->
<div class="modal fade" id="addProviderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Add IPTV Provider</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="addProviderForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= Auth::generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="add_provider">
                    
                    <div class="mb-4">
                        <label for="providerName" class="form-label">Provider Name</label>
                        <input type="text" class="form-control" id="providerName" name="name" 
                               placeholder="e.g., My IPTV Provider" required>
                        <div class="form-text">Give your provider a memorable name</div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="providerType" class="form-label">Provider Type</label>
                        <select class="form-select" id="providerType" name="type" required onchange="toggleProviderFields()">
                            <option value="">Select provider type</option>
                            <option value="m3u">M3U Playlist</option>
                            <option value="xtream">Xtream Codes API</option>
                        </select>
                    </div>
                    
                    <!-- M3U Fields -->
                    <div id="m3uFields" style="display: none;">
                        <div class="mb-4">
                            <label for="m3uUrl" class="form-label">M3U Playlist URL</label>
                            <input type="url" class="form-control" id="m3uUrl" name="url" 
                                   placeholder="http://example.com/playlist.m3u8">
                            <div class="form-text">The direct link to your M3U playlist file</div>
                        </div>
                    </div>
                    
                    <!-- Xtream Codes Fields -->
                    <div id="xtreamFields" style="display: none;">
                        <div class="mb-4">
                            <label for="xtreamUrl" class="form-label">Server URL</label>
                            <input type="url" class="form-control" id="xtreamUrl" name="url" 
                                   placeholder="http://example.com:8080">
                            <div class="form-text">Your Xtream Codes server URL (without /get.php)</div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="xtreamUsername" class="form-label">Username</label>
                                <input type="text" class="form-control" id="xtreamUsername" name="username" 
                                       placeholder="Your username">
                            </div>
                            <div class="col-md-6">
                                <label for="xtreamPassword" class="form-label">Password</label>
                                <input type="password" class="form-control" id="xtreamPassword" name="password" 
                                       placeholder="Your password">
                            </div>
                        </div>
                    </div>
                    
                    <!-- EPG URL (Optional) -->
                    <div class="mb-4">
                        <label for="epgUrl" class="form-label">XMLTV EPG URL <span class="text-muted">(Optional)</span></label>
                        <input type="url" class="form-control" id="epgUrl" name="epg_url" 
                               placeholder="http://example.com/epg.xml">
                        <div class="form-text">Electronic Program Guide for TV schedules</div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Note:</strong> The initial setup may take a few minutes as we import your channels and EPG data.
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="bi bi-plus-circle me-2"></i>Add Provider
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleProviderFields() {
    const type = document.getElementById('providerType').value;
    const m3uFields = document.getElementById('m3uFields');
    const xtreamFields = document.getElementById('xtreamFields');
    const m3uUrl = document.getElementById('m3uUrl');
    const xtreamUrl = document.getElementById('xtreamUrl');
    const xtreamUsername = document.getElementById('xtreamUsername');
    const xtreamPassword = document.getElementById('xtreamPassword');
    
    if (type === 'm3u') {
        m3uFields.style.display = 'block';
        xtreamFields.style.display = 'none';
        m3uUrl.required = true;
        xtreamUrl.required = false;
        xtreamUsername.required = false;
        xtreamPassword.required = false;
    } else if (type === 'xtream') {
        m3uFields.style.display = 'none';
        xtreamFields.style.display = 'block';
        m3uUrl.required = false;
        xtreamUrl.required = true;
        xtreamUsername.required = true;
        xtreamPassword.required = true;
    } else {
        m3uFields.style.display = 'none';
        xtreamFields.style.display = 'none';
        m3uUrl.required = false;
        xtreamUrl.required = false;
        xtreamUsername.required = false;
        xtreamPassword.required = false;
    }
    
    // Update URL field name based on type
    if (type === 'm3u') {
        document.getElementById('m3uUrl').name = 'url';
        document.getElementById('xtreamUrl').name = '';
    } else if (type === 'xtream') {
        document.getElementById('m3uUrl').name = '';
        document.getElementById('xtreamUrl').name = 'url';
    }
}

document.getElementById('addProviderForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-2" role="status"></div>Adding...';
});
</script>

<?php include 'includes/footer.php'; ?>