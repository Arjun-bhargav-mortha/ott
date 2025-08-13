</main>
    
    <!-- Footer -->
    <footer class="bg-dark text-light py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5 class="text-primary mb-3">
                        <i class="bi bi-play-circle-fill me-2"></i>StreamFlix Pro
                    </h5>
                    <p class="text-muted">
                        Premium IPTV streaming platform with unlimited access to live TV, 
                        movies, and series from around the world.
                    </p>
                </div>
                <div class="col-md-2">
                    <h6 class="mb-3">Platform</h6>
                    <ul class="list-unstyled">
                        <li><a href="/live.php" class="text-muted text-decoration-none">Live TV</a></li>
                        <li><a href="/movies.php" class="text-muted text-decoration-none">Movies</a></li>
                        <li><a href="/series.php" class="text-muted text-decoration-none">Series</a></li>
                        <li><a href="/favorites.php" class="text-muted text-decoration-none">My List</a></li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <h6 class="mb-3">Account</h6>
                    <ul class="list-unstyled">
                        <li><a href="/profiles.php" class="text-muted text-decoration-none">Profiles</a></li>
                        <li><a href="/account.php" class="text-muted text-decoration-none">Settings</a></li>
                        <li><a href="/provider.php" class="text-muted text-decoration-none">IPTV Setup</a></li>
                        <li><a href="/help.php" class="text-muted text-decoration-none">Help Center</a></li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <h6 class="mb-3">Support</h6>
                    <ul class="list-unstyled">
                        <li><a href="/contact.php" class="text-muted text-decoration-none">Contact Us</a></li>
                        <li><a href="/privacy.php" class="text-muted text-decoration-none">Privacy Policy</a></li>
                        <li><a href="/terms.php" class="text-muted text-decoration-none">Terms of Service</a></li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <h6 class="mb-3">Connect</h6>
                    <div class="d-flex gap-2">
                        <a href="#" class="text-muted"><i class="bi bi-facebook fs-5"></i></a>
                        <a href="#" class="text-muted"><i class="bi bi-twitter fs-5"></i></a>
                        <a href="#" class="text-muted"><i class="bi bi-instagram fs-5"></i></a>
                        <a href="#" class="text-muted"><i class="bi bi-youtube fs-5"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-muted mb-0">
                        &copy; <?= date('Y') ?> StreamFlix Pro. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">
                        <i class="bi bi-shield-check me-1"></i>
                        Secure • Private • Premium
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="/assets/js/main.js"></script>
    
    <!-- Page-specific JavaScript -->
    <?php if (isset($additionalJS)): ?>
        <?= $additionalJS ?>
    <?php endif; ?>
</body>
</html>