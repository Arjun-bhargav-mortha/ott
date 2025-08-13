/**
 * StreamFlix Pro - Main JavaScript
 */

class StreamFlixApp {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeComponents();
        this.setupSearch();
        this.setupFavorites();
        this.setupVideoPlayer();
        this.setupInfiniteScroll();
    }

    setupEventListeners() {
        // Navigation active state
        this.setActiveNavItem();
        
        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(anchor.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });

        // Back to top button
        this.createBackToTopButton();
        
        // Modal enhancements
        this.enhanceModals();
        
        // Keyboard shortcuts
        this.setupKeyboardShortcuts();
    }

    setActiveNavItem() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href === currentPath || (currentPath === '/' && href === '/')) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }

    createBackToTopButton() {
        const backToTop = document.createElement('button');
        backToTop.innerHTML = '<i class="bi bi-arrow-up"></i>';
        backToTop.className = 'btn btn-primary position-fixed';
        backToTop.style.cssText = `
            bottom: 2rem; 
            right: 2rem; 
            z-index: 1000; 
            border-radius: 50%; 
            width: 50px; 
            height: 50px; 
            opacity: 0; 
            transform: translateY(20px);
            transition: all 0.3s ease;
        `;
        
        document.body.appendChild(backToTop);

        // Show/hide based on scroll
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTop.style.opacity = '1';
                backToTop.style.transform = 'translateY(0)';
            } else {
                backToTop.style.opacity = '0';
                backToTop.style.transform = 'translateY(20px)';
            }
        });

        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    enhanceModals() {
        // Auto-focus first input in modals
        document.addEventListener('shown.bs.modal', (e) => {
            const firstInput = e.target.querySelector('input, textarea, select');
            if (firstInput) {
                firstInput.focus();
            }
        });
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Search shortcut (Ctrl/Cmd + K)
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const searchInput = document.querySelector('input[name="q"]');
                if (searchInput) {
                    searchInput.focus();
                }
            }
            
            // Escape key to close modals/overlays
            if (e.key === 'Escape') {
                const activeModal = document.querySelector('.modal.show');
                if (activeModal) {
                    bootstrap.Modal.getInstance(activeModal).hide();
                }
            }
        });
    }

    initializeComponents() {
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Initialize popovers
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });

        // Lazy loading for images
        this.setupLazyLoading();
        
        // Content card hover effects
        this.setupContentCardEffects();
    }

    setupLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }

    setupContentCardEffects() {
        const cards = document.querySelectorAll('.content-card');
        
        cards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                this.showCardPreview(card);
            });
            
            card.addEventListener('mouseleave', () => {
                this.hideCardPreview(card);
            });
        });
    }

    showCardPreview(card) {
        const overlay = card.querySelector('.content-card-overlay');
        if (overlay) {
            overlay.style.opacity = '1';
        }
        
        // Add preview controls if not already present
        if (!card.querySelector('.preview-controls')) {
            this.addPreviewControls(card);
        }
    }

    hideCardPreview(card) {
        const overlay = card.querySelector('.content-card-overlay');
        if (overlay) {
            overlay.style.opacity = '0';
        }
    }

    addPreviewControls(card) {
        const controls = document.createElement('div');
        controls.className = 'preview-controls d-flex gap-2 mt-2';
        controls.innerHTML = `
            <button class="btn btn-light btn-sm" onclick="playContent(this)" data-action="play">
                <i class="bi bi-play-fill"></i>
            </button>
            <button class="btn btn-outline-light btn-sm" onclick="addToList(this)" data-action="add">
                <i class="bi bi-plus"></i>
            </button>
            <button class="btn btn-outline-light btn-sm" onclick="showInfo(this)" data-action="info">
                <i class="bi bi-info-circle"></i>
            </button>
        `;
        
        const overlay = card.querySelector('.content-card-overlay');
        if (overlay) {
            overlay.appendChild(controls);
        }
    }

    setupSearch() {
        const searchInput = document.querySelector('input[name="q"]');
        if (!searchInput) return;

        let searchTimeout;
        
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.performSearch(e.target.value);
            }, 300);
        });

        // Close search results when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-container')) {
                this.hideSearchResults();
            }
        });
    }

    async performSearch(query) {
        if (query.length < 2) {
            this.hideSearchResults();
            return;
        }

        try {
            const response = await fetch(`/api/search.php?q=${encodeURIComponent(query)}`);
            const results = await response.json();
            this.displaySearchResults(results);
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    displaySearchResults(results) {
        const searchContainer = document.querySelector('.search-container');
        if (!searchContainer) return;

        let resultsContainer = searchContainer.querySelector('.search-results');
        if (!resultsContainer) {
            resultsContainer = document.createElement('div');
            resultsContainer.className = 'search-results';
            searchContainer.appendChild(resultsContainer);
        }

        if (results.length === 0) {
            resultsContainer.innerHTML = '<div class="search-result-item text-muted">No results found</div>';
        } else {
            resultsContainer.innerHTML = results.map(result => `
                <div class="search-result-item" onclick="location.href='${result.url}'">
                    <div class="d-flex align-items-center">
                        <img src="${result.poster || '/assets/images/placeholder-poster.jpg'}" 
                             alt="${result.name}" width="40" height="60" class="me-3">
                        <div>
                            <div class="fw-semibold">${result.name}</div>
                            <small class="text-muted">${result.type} • ${result.year || 'N/A'}</small>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        resultsContainer.style.display = 'block';
    }

    hideSearchResults() {
        const resultsContainer = document.querySelector('.search-results');
        if (resultsContainer) {
            resultsContainer.style.display = 'none';
        }
    }

    setupFavorites() {
        document.addEventListener('click', (e) => {
            if (e.target.matches('.favorite-btn') || e.target.closest('.favorite-btn')) {
                e.preventDefault();
                this.toggleFavorite(e.target.closest('.favorite-btn'));
            }
        });
    }

    async toggleFavorite(button) {
        const titleId = button.dataset.titleId;
        const channelId = button.dataset.channelId;
        const isActive = button.classList.contains('active');

        try {
            const response = await fetch('/api/favorites.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    action: isActive ? 'remove' : 'add',
                    title_id: titleId,
                    channel_id: channelId,
                    csrf_token: document.querySelector('meta[name="csrf-token"]')?.content
                })
            });

            const result = await response.json();
            
            if (result.success) {
                button.classList.toggle('active');
                const icon = button.querySelector('i');
                if (button.classList.contains('active')) {
                    icon.className = 'bi bi-heart-fill';
                    this.showToast('Added to My List', 'success');
                } else {
                    icon.className = 'bi bi-heart';
                    this.showToast('Removed from My List', 'info');
                }
            } else {
                this.showToast(result.message || 'Failed to update favorites', 'error');
            }
        } catch (error) {
            console.error('Favorites error:', error);
            this.showToast('Failed to update favorites', 'error');
        }
    }

    setupVideoPlayer() {
        const videoElements = document.querySelectorAll('.video-player');
        
        videoElements.forEach(video => {
            this.initializePlayer(video);
        });
    }

    initializePlayer(videoElement) {
        if (!videoElement) return;

        const src = videoElement.dataset.src;
        if (!src) return;

        // Check if HLS is supported
        if (Hls.isSupported()) {
            const hls = new Hls({
                enableWorker: true,
                lowLatencyMode: true,
                backBufferLength: 90
            });
            
            hls.loadSource(src);
            hls.attachMedia(videoElement);
            
            hls.on(Hls.Events.MANIFEST_PARSED, () => {
                console.log('HLS manifest loaded');
                this.setupPlayerControls(videoElement, hls);
            });
            
            hls.on(Hls.Events.ERROR, (event, data) => {
                console.error('HLS error:', data);
                if (data.fatal) {
                    this.handlePlayerError(videoElement, data);
                }
            });
            
            videoElement.hls = hls;
        } else if (videoElement.canPlayType('application/vnd.apple.mpegurl')) {
            // Native HLS support (Safari)
            videoElement.src = src;
            this.setupPlayerControls(videoElement);
        } else {
            this.showToast('Video format not supported', 'error');
        }
    }

    setupPlayerControls(videoElement, hls = null) {
        const container = videoElement.closest('.video-container');
        if (!container) return;

        // Resume playback position
        this.restorePlaybackPosition(videoElement);
        
        // Save progress periodically
        videoElement.addEventListener('timeupdate', () => {
            this.savePlaybackProgress(videoElement);
        });

        // Quality selector for HLS
        if (hls && hls.levels.length > 1) {
            this.addQualitySelector(container, hls);
        }

        // Custom controls
        this.addCustomControls(container, videoElement);
    }

    addQualitySelector(container, hls) {
        const qualitySelector = document.createElement('select');
        qualitySelector.className = 'form-select form-select-sm ms-2';
        qualitySelector.style.width = 'auto';
        
        // Add auto quality option
        qualitySelector.innerHTML = '<option value="-1">Auto</option>';
        
        // Add quality levels
        hls.levels.forEach((level, index) => {
            const option = document.createElement('option');
            option.value = index;
            option.textContent = `${level.height}p`;
            qualitySelector.appendChild(option);
        });

        qualitySelector.addEventListener('change', (e) => {
            hls.currentLevel = parseInt(e.target.value);
        });

        const controls = container.querySelector('.video-controls');
        if (controls) {
            controls.appendChild(qualitySelector);
        }
    }

    addCustomControls(container, videoElement) {
        // Progress bar interaction
        const progressBar = container.querySelector('.progress-bar');
        if (progressBar) {
            progressBar.addEventListener('click', (e) => {
                const rect = progressBar.getBoundingClientRect();
                const pos = (e.clientX - rect.left) / rect.width;
                videoElement.currentTime = pos * videoElement.duration;
            });
        }

        // Update progress
        videoElement.addEventListener('timeupdate', () => {
            const progressFill = container.querySelector('.progress-fill');
            if (progressFill && videoElement.duration) {
                const progress = (videoElement.currentTime / videoElement.duration) * 100;
                progressFill.style.width = progress + '%';
            }
        });
    }

    restorePlaybackPosition(videoElement) {
        const titleId = videoElement.dataset.titleId;
        const episodeId = videoElement.dataset.episodeId;
        
        if (titleId || episodeId) {
            const savedPosition = localStorage.getItem(`playback_${titleId || episodeId}`);
            if (savedPosition && parseFloat(savedPosition) > 10) {
                videoElement.currentTime = parseFloat(savedPosition);
            }
        }
    }

    savePlaybackProgress(videoElement) {
        const titleId = videoElement.dataset.titleId;
        const episodeId = videoElement.dataset.episodeId;
        
        if (titleId || episodeId) {
            const key = `playback_${titleId || episodeId}`;
            localStorage.setItem(key, videoElement.currentTime.toString());
            
            // Also save to server periodically
            if (Math.floor(videoElement.currentTime) % 30 === 0) {
                this.saveProgressToServer(titleId, episodeId, videoElement.currentTime, videoElement.duration);
            }
        }
    }

    async saveProgressToServer(titleId, episodeId, position, duration) {
        try {
            await fetch('/api/progress.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    title_id: titleId,
                    episode_id: episodeId,
                    position: Math.floor(position),
                    duration: Math.floor(duration),
                    csrf_token: document.querySelector('meta[name="csrf-token"]')?.content
                })
            });
        } catch (error) {
            console.error('Failed to save progress:', error);
        }
    }

    handlePlayerError(videoElement, errorData) {
        console.error('Player error:', errorData);
        
        const container = videoElement.closest('.video-container');
        const errorMsg = document.createElement('div');
        errorMsg.className = 'alert alert-danger text-center';
        errorMsg.innerHTML = `
            <i class="bi bi-exclamation-triangle me-2"></i>
            Unable to load video. Please try again later.
        `;
        
        if (container) {
            container.appendChild(errorMsg);
        }
    }

    setupInfiniteScroll() {
        const loadMoreButtons = document.querySelectorAll('[data-load-more]');
        
        loadMoreButtons.forEach(button => {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.loadMoreContent(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });
            
            observer.observe(button);
        });
    }

    async loadMoreContent(button) {
        const url = button.dataset.loadMore;
        const container = button.dataset.container;
        
        if (!url || !container) return;

        button.innerHTML = '<div class="loading-spinner"></div> Loading...';
        button.disabled = true;

        try {
            const response = await fetch(url);
            const html = await response.text();
            
            const targetContainer = document.querySelector(container);
            if (targetContainer) {
                targetContainer.insertAdjacentHTML('beforeend', html);
            }
            
            button.remove();
        } catch (error) {
            console.error('Load more error:', error);
            button.innerHTML = 'Load More';
            button.disabled = false;
        }
    }

    showToast(message, type = 'info') {
        // Create toast container if it doesn't exist
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }

        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${this.getToastClass(type)} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        toastContainer.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
        bsToast.show();

        // Remove toast element after it's hidden
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }

    getToastClass(type) {
        const classes = {
            'success': 'success',
            'error': 'danger',
            'warning': 'warning',
            'info': 'info'
        };
        return classes[type] || 'info';
    }
}

// Global functions for inline event handlers
window.playContent = function(button) {
    const card = button.closest('.content-card');
    const titleId = card.dataset.titleId;
    const episodeId = card.dataset.episodeId;
    const channelId = card.dataset.channelId;
    
    let url = '/watch.php?';
    if (titleId) url += `title=${titleId}`;
    if (episodeId) url += `episode=${episodeId}`;
    if (channelId) url += `channel=${channelId}`;
    
    window.location.href = url;
};

window.addToList = function(button) {
    const favoriteBtn = button.closest('.content-card').querySelector('.favorite-btn');
    if (favoriteBtn) {
        favoriteBtn.click();
    }
};

window.showInfo = function(button) {
    const card = button.closest('.content-card');
    const titleId = card.dataset.titleId;
    const channelId = card.dataset.channelId;
    
    let url = titleId ? `/title.php?id=${titleId}` : `/channel.php?id=${channelId}`;
    window.location.href = url;
};

// Initialize the app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.streamFlixApp = new StreamFlixApp();
});

// Service Worker registration for offline functionality
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js')
        .then(registration => console.log('SW registered:', registration))
        .catch(error => console.log('SW registration failed:', error));
}