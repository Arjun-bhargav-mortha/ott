<?php
require_once 'config/database.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

Auth::init();
Auth::requireLogin();

$storage = getStorage();
$pageTitle = 'Movies - StreamFlix Pro';

// Get filter parameters
$genre = sanitize($_GET['genre'] ?? '');
$year = intval($_GET['year'] ?? 0);
$search = sanitize($_GET['search'] ?? '');
$sort = sanitize($_GET['sort'] ?? 'name');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 24;

// Get all movies
$allTitles = $storage->read('titles');
$movies = array_filter($allTitles, function($t) {
    return $t['type'] === 'movie' && $t['status'] === 'active';
});

// Apply filters
if (!empty($genre)) {
    $movies = array_filter($movies, function($m) use ($genre) {
        return in_array($genre, $m['genres'] ?? []);
    });
}

if ($year > 0) {
    $movies = array_filter($movies, function($m) use ($year) {
        return $m['year'] == $year;
    });
}

if (!empty($search)) {
    $movies = array_filter($movies, function($m) use ($search) {
        return stripos($m['name'], $search) !== false || 
               stripos($m['description'] ?? '', $search) !== false;
    });
}

// Sort movies
switch ($sort) {
    case 'year':
        usort($movies, function($a, $b) {
            return ($b['year'] ?? 0) <=> ($a['year'] ?? 0);
        });
        break;
    case 'rating':
        usort($movies, function($a, $b) {
            return ($b['imdb_rating'] ?? 0) <=> ($a['imdb_rating'] ?? 0);
        });
        break;
    case 'name':
    default:
        usort($movies, function($a, $b) {
            return $a['name'] <=> $b['name'];
        });
        break;
}

// Pagination
$totalMovies = count($movies);
$totalPages = ceil($totalMovies / $perPage);
$offset = ($page - 1) * $perPage;
$movies = array_slice($movies, $offset, $perPage);

// Get available genres and years for filters
$allGenres = [];
$allYears = [];
foreach ($allTitles as $title) {
    if ($title['type'] === 'movie' && $title['status'] === 'active') {
        if (!empty($title['genres'])) {
            $allGenres = array_merge($allGenres, $title['genres']);
        }
        if (!empty($title['year'])) {
            $allYears[] = $title['year'];
        }
    }
}
$allGenres = array_unique($allGenres);
$allYears = array_unique($allYears);
rsort($allYears);

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <h1 class="section-title mb-4">
                <i class="bi bi-film me-2"></i>Movies
                <span class="badge bg-primary ms-2"><?= number_format($totalMovies) ?> movies</span>
            </h1>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-dark border-secondary">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?= htmlspecialchars($search) ?>" placeholder="Search movies...">
                        </div>
                        <div class="col-md-2">
                            <label for="genre" class="form-label">Genre</label>
                            <select class="form-select" id="genre" name="genre">
                                <option value="">All Genres</option>
                                <?php foreach ($allGenres as $g): ?>
                                <option value="<?= htmlspecialchars($g) ?>" <?= $genre === $g ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($g) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="year" class="form-label">Year</label>
                            <select class="form-select" id="year" name="year">
                                <option value="">All Years</option>
                                <?php foreach ($allYears as $y): ?>
                                <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>>
                                    <?= $y ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="sort" class="form-label">Sort By</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name</option>
                                <option value="year" <?= $sort === 'year' ? 'selected' : '' ?>>Year</option>
                                <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Rating</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-search me-1"></i>Filter
                            </button>
                            <a href="/movies.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise me-1"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (empty($movies)): ?>
    <div class="row">
        <div class="col-12">
            <div class="text-center py-5">
                <i class="bi bi-film text-muted" style="font-size: 4rem;"></i>
                <h3 class="mt-3 mb-2">No Movies Found</h3>
                <p class="text-muted mb-4">
                    <?php if (!empty($search) || !empty($genre) || $year > 0): ?>
                    Try adjusting your filters or search terms.
                    <?php else: ?>
                    Add an IPTV provider with VOD content to see movies here.
                    <?php endif; ?>
                </p>
                <?php if (empty($search) && empty($genre) && $year === 0): ?>
                <a href="/provider.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-plus-circle me-2"></i>Add IPTV Provider
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php else: ?>
    
    <!-- Movies Grid -->
    <div class="row">
        <?php foreach ($movies as $movie): ?>
        <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-4">
            <div class="content-card" data-title-id="<?= $movie['id'] ?>">
                <div class="position-relative">
                    <img src="<?= getPosterUrl($movie['poster']) ?>" 
                         class="content-card-img" 
                         alt="<?= htmlspecialchars($movie['name']) ?>"
                         loading="lazy">
                    
                    <button class="favorite-btn" data-title-id="<?= $movie['id'] ?>">
                        <i class="bi bi-heart"></i>
                    </button>
                    
                    <div class="content-card-overlay">
                        <div>
                            <h6 class="text-white mb-1"><?= htmlspecialchars($movie['name']) ?></h6>
                            <div class="d-flex align-items-center text-light mb-2">
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
                            <?php if ($movie['duration']): ?>
                            <small class="text-muted"><?= formatDuration($movie['duration']) ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="content-card-body">
                    <h6 class="content-card-title"><?= htmlspecialchars($movie['name']) ?></h6>
                    <?php if (!empty($movie['genres'])): ?>
                    <p class="content-card-text"><?= implode(', ', array_slice($movie['genres'], 0, 2)) ?></p>
                    <?php endif; ?>
                    <div class="content-card-meta">
                        <?php if ($movie['year']): ?>
                        <span><?= $movie['year'] ?></span>
                        <?php endif; ?>
                        <?php if ($movie['imdb_rating']): ?>
                        <span><i class="bi bi-star-fill text-warning"></i> <?= $movie['imdb_rating'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="row">
        <div class="col-12">
            <nav aria-label="Movies pagination">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </div>
    <?php endif; ?>
    
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>