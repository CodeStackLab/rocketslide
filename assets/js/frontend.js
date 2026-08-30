(function () {
    'use strict';

    // Retrieve data passed from PHP template
    var data = window.ROCKETSLIDE_DATA || {};
    var images = data.images || [];
    var fallbackUrl = data.fallback_url || 'https://google.com';
    var isTestMode = Boolean(data.is_test_mode) || (window.location.search.indexOf('test_mode=1') !== -1);

    var container = document.getElementById('rocketslide-reels-container');
    var progressBarContainer = document.getElementById('redirect-progress-bar-container');
    var progressBar = document.getElementById('redirect-progress-bar');

    var currentIndex = 0;
    var batchSize = 5;
    var isTimerActive = false;

    // Toast Notice for Test Mode & Interactivity
    function showTestNotice(msg) {
        var existing = document.getElementById('rocketslide-test-toast');
        if (existing) existing.remove();

        var toast = document.createElement('div');
        toast.id = 'rocketslide-test-toast';
        toast.style.cssText = 'position:fixed; bottom:20px; left:50%; transform:translateX(-50%); background:rgba(255,255,255,0.96); border:1px solid #2563eb; color:#0f172a; padding:10px 20px; border-radius:30px; font-size:12px; font-weight:700; z-index:99999; text-align:center; box-shadow:0 8px 30px rgba(15,23,42,0.15); pointer-events:none; backdrop-filter:blur(10px);';
        toast.innerText = msg;
        document.body.appendChild(toast);

        setTimeout(function () {
            if (toast && toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 3000);
    }

    // 1. Dynamic Image Array Random Shuffling (Every Visit/Reload)
    function shuffleArray(arr) {
        return arr.sort(function () {
            return Math.random() - 0.5;
        });
    }

    images = shuffleArray(images);

    // 2. Query Parameter Preservation & Forwarding Helper
    function buildTargetUrlWithParams(targetUrl) {
        if (!targetUrl || targetUrl === '') {
            targetUrl = fallbackUrl;
        }

        // Auto-prepend https:// if missing protocol
        if (!targetUrl.match(/^https?:\/\//i) && !targetUrl.startsWith('/')) {
            targetUrl = 'https://' + targetUrl;
        }

        try {
            var incomingParams = new URLSearchParams(window.location.search);
            var destUrlObj = new URL(targetUrl, window.location.origin);

            // Forward all query parameters (fbclid, utm_*, gclid, etc.)
            incomingParams.forEach(function (value, key) {
                destUrlObj.searchParams.set(key, value);
            });

            return destUrlObj.toString();
        } catch (e) {
            return targetUrl;
        }
    }

    // 3. Render Clean 9:16 Reel Card (No Like, Comment, Profile, Follow, or Captions)
    function createReelCard(imageItem, index) {
        var card = document.createElement('div');
        card.className = 'reel-card';
        card.setAttribute('data-target-url', imageItem.target_url || fallbackUrl);
        card.setAttribute('data-timer', imageItem.timer || 0);

        // 9:16 Full Screen Image
        var img = document.createElement('img');
        img.className = 'reel-img';
        img.src = imageItem.url;
        img.alt = 'Video Content';

        if (index < 2) {
            img.setAttribute('loading', 'eager');
        } else {
            img.setAttribute('loading', 'lazy');
            img.setAttribute('decoding', 'async');
        }

        card.appendChild(img);

        // Subtle Linear Gradient Shadow
        var gradient = document.createElement('div');
        gradient.className = 'reel-overlay-gradient';
        card.appendChild(gradient);

        // Center Glassmorphism Play Button Overlay
        var playOverlay = document.createElement('div');
        playOverlay.className = 'reel-play-overlay';
        playOverlay.innerHTML = `
            <svg viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z"/>
            </svg>
        `;
        card.appendChild(playOverlay);

        // ANY Click/Tap on Card -> Instant Target URL Redirect
        card.addEventListener('click', function () {
            var destUrl = buildTargetUrlWithParams(imageItem.target_url);
            if (isTestMode) {
                showTestNotice('⚡ Test Mode: Bypassed redirect to ' + destUrl);
            } else {
                window.location.replace(destUrl);
            }
        });

        return card;
    }

    // 4. Batch Loading for Infinite Scroll
    function loadNextBatch() {
        if (images.length === 0) {
            var emptyCard = document.createElement('div');
            emptyCard.className = 'reel-card';
            emptyCard.style.padding = '20px';
            emptyCard.style.textAlign = 'center';
            emptyCard.innerHTML = '<div style="margin:auto;"><h2>Exclusive Video</h2><p style="margin-top:10px; opacity:0.8;">Tap anywhere to watch</p></div>';
            emptyCard.addEventListener('click', function () {
                var destUrl = buildTargetUrlWithParams(fallbackUrl);
                if (isTestMode) {
                    showTestNotice('⚡ Test Mode Active: External redirect bypassed for testing');
                } else {
                    window.location.replace(destUrl);
                }
            });
            container.appendChild(emptyCard);
            return;
        }

        var end = Math.min(currentIndex + batchSize, images.length);
        for (var i = currentIndex; i < end; i++) {
            var card = createReelCard(images[i], i);
            container.appendChild(card);
        }
        currentIndex = end;
    }

    // Initial Load of Images
    loadNextBatch();

    // 5. Infinite Scroll Event Listener
    container.addEventListener('scroll', function () {
        if (container.scrollTop + container.clientHeight >= container.scrollHeight - 300) {
            if (currentIndex < images.length) {
                loadNextBatch();
            }
        }
    });

    // 6. Auto-Redirect Timer & Animated Progress Bar (Disabled in Test Mode)
    if (images.length > 0 && !isTestMode) {
        var topImage = images[0];
        var timerSeconds = parseInt(topImage.timer, 10) || 0;

        if (timerSeconds > 0) {
            isTimerActive = true;
            progressBarContainer.style.display = 'block';
            
            var startTime = Date.now();
            var durationMs = timerSeconds * 1000;

            var interval = setInterval(function () {
                var elapsed = Date.now() - startTime;
                var progressPercent = Math.min((elapsed / durationMs) * 100, 100);
                progressBar.style.width = progressPercent + '%';

                if (elapsed >= durationMs) {
                    clearInterval(interval);
                    var destUrl = buildTargetUrlWithParams(topImage.target_url);
                    window.location.replace(destUrl);
                }
            }, 50);
        }
    }

})();
