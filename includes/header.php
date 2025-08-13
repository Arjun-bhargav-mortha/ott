<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'StreamFlix Pro' ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    
    <!-- HLS.js for video streaming -->
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    
    <!-- Custom favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold text-primary" href="/">
                <i class="bi bi-play-circle-fill me-2"></i>StreamFlix Pro
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if (Auth::isLoggedIn()): ?>
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/live.php">Live TV</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/movies.php">Movies</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/series.php">Series</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/favorites.php">My List</a>
                    </li>
                </ul>
                
                <!-- Search -->
                <form class="d-flex me-3" method="GET" action="/search.php">
                    <div class="input-group">
                        <input class="form-control bg-dark border-0" type="search" name="q" 
                               placeholder="Search..." value="<?= sanitize($_GET['q'] ?? '') ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
                
                <!-- User Menu -->
                <div class="dropdown">
                    <?php $profile = Auth::getCurrentProfile(); ?>
                    <a class="btn btn-outline-light dropdown-toggle" href="#" role="button" 
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="/assets/images/avatars/<?= $profile['avatar'] ?>" 
                             alt="Profile" width="24" height="24" class="rounded-circle me-2">
                        <?= htmlspecialchars($profile['name']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="/profiles.php">Switch Profiles</a></li>
                        <li><a class="dropdown-item" href="/account.php">Account Settings</a></li>
                        <li><a class="dropdown-item" href="/provider.php">IPTV Settings</a></li>
                        <?php if (Auth::getUserRole() === 'admin'): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/admin/">Admin Panel</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/logout.php">Sign Out</a></li>
                    </ul>
                </div>
                <?php else: ?>
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="/login.php">Sign In</a>
                    <a class="nav-link" href="/register.php">Sign Up</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="main-content">