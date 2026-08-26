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

    // Create Comments Drawer Modal Element
    var commentsDrawer = document.createElement('div');
    commentsDrawer.className = 'reel-comments-drawer';
    commentsDrawer.innerHTML = `
        <div class="comments-header">
            <span class="comments-title" id="comments-title">3.4K Comments</span>
            <button class="comments-close-btn" id="comments-close-btn">✕</button>
        </div>
        <div class="comments-list" id="comments-list">
            <div class="comment-item">
                <div class="comment-avatar">👤</div>
                <div class="comment-body">
                    <div class="comment-author">@alex_vibe</div>
                    <div class="comment-text">OMG wait for the end! 😱🔥</div>
                    <div class="comment-time">2h ago • Reply</div>
                </div>
            </div>
            <div class="comment-item">
                <div class="comment-avatar">🔥</div>
                <div class="comment-body">
                    <div class="comment-author">@sarah_trends</div>
                    <div class="comment-text">Is this real?! Tap the link guys! 👇</div>
                    <div class="comment-time">4h ago • Reply</div>
                </div>
            </div>
            <div class="comment-item">
                <div class="comment-avatar">⚡</div>
                <div class="comment-body">
                    <div class="comment-author">@mike_reels</div>
                    <div class="comment-text">Best video on my fyp today 😂</div>
                    <div class="comment-time">6h ago • Reply</div>
                </div>
            </div>
        </div>
        <div class="comments-input-bar">
            <input type="text" class="comments-input" id="comments-input-field" placeholder="Add comment...">
            <button class="comments-send-btn" id="comments-send-btn">Post</button>
        </div>
    `;
    document.body.appendChild(commentsDrawer);

    // Comments Drawer Event Handlers
    document.getElementById('comments-close-btn').addEventListener('click', function (e) {
        e.stopPropagation();
        commentsDrawer.classList.remove('open');
    });

    function postComment() {
        var input = document.getElementById('comments-input-field');
        var val = input.value.trim();
        if (!val) return;

        var list = document.getElementById('comments-list');
        var item = document.createElement('div');
        item.className = 'comment-item';
        item.innerHTML = `
            <div class="comment-avatar">👤</div>
            <div class="comment-body">
                <div class="comment-author">@you</div>
                <div class="comment-text">${escapeHtml(val)}</div>
                <div class="comment-time">Just now • Reply</div>
            </div>
        `;
        list.prepend(item);
        input.value = '';
    }

    document.getElementById('comments-send-btn').addEventListener('click', function (e) {
        e.stopPropagation();
        postComment();
    });

    document.getElementById('comments-input-field').addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            e.stopPropagation();
            postComment();
        }
    });

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

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

    // 3. Render Reel Card Item with TikTok/Reels Interactive Social UI
    function createReelCard(imageItem, index) {
        var card = document.createElement('div');
        card.className = 'reel-card';
        card.setAttribute('data-target-url', imageItem.target_url || fallbackUrl);
        card.setAttribute('data-timer', imageItem.timer || 0);

        var username       = imageItem.username || '@viral_reels_official';
        var userAvatar     = imageItem.user_avatar || imageItem.url || '';
        var caption        = imageItem.caption || '';
        var likesCount     = imageItem.likes_count || '142.8K';
        var commentsCount  = imageItem.comments_count || '3.4K';
        var sharesCount    = imageItem.shares_count || '18.9K';

        // 9:16 Full Screen Image
        var img = document.createElement('img');
        img.className = 'reel-img';
        img.src = imageItem.url;
        img.alt = 'Reel Content';

        if (index < 2) {
            img.setAttribute('loading', 'eager');
        } else {
            img.setAttribute('loading', 'lazy');
            img.setAttribute('decoding', 'async');
        }

        card.appendChild(img);

        // Linear Gradient Shadow for contrast
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

        // Bottom-Left Author & Info Block
        var infoBlock = document.createElement('div');
        infoBlock.className = 'reel-info-block';

        var avatarMarkup = userAvatar 
            ? `<img src="${userAvatar}" class="reel-avatar-img" alt="Avatar" style="width:28px; height:28px; border-radius:50%; border:1.5px solid #ffffff;">` 
            : `<div class="reel-avatar-placeholder" style="width:28px; height:28px; font-size:13px; border-radius:50%; border:1.5px solid #ffffff;">👤</div>`;

        var captionMarkup = caption ? `<div class="reel-caption">${escapeHtml(caption)}</div>` : '';

        infoBlock.innerHTML = `
            <div class="reel-user-row">
                ${avatarMarkup}
                <span class="reel-username">${escapeHtml(username)}</span>
                <button class="reel-follow-pill">Follow</button>
            </div>
            ${captionMarkup}
            <div class="reel-audio-row">
                <svg viewBox="0 0 24 24" width="13" height="13" fill="#2563eb" style="flex-shrink:0;">
                    <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>
                </svg>
                <span class="reel-audio-title">Original Audio • ${escapeHtml(username.replace(/^@/, ''))}</span>
            </div>
        `;
        card.appendChild(infoBlock);

        // Right Sidebar Social Interaction Bar (Light Glass Authentic SVGs)
        var actionSidebar = document.createElement('div');
        actionSidebar.className = 'reel-action-bar';

        var avatarSidebarMarkup = userAvatar
            ? `<img src="${userAvatar}" class="reel-avatar-img" alt="Avatar">`
            : `<div class="reel-avatar-placeholder">👤</div>`;

        actionSidebar.innerHTML = `
            <div class="reel-avatar-wrapper">
                ${avatarSidebarMarkup}
                <div class="reel-follow-plus">+</div>
            </div>
            <button class="reel-action-btn action-like-btn">
                <span class="reel-action-icon">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="#0f172a">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                    </svg>
                </span>
                <span class="reel-action-count count-likes">${escapeHtml(likesCount)}</span>
            </button>
            <button class="reel-action-btn action-comment-btn">
                <span class="reel-action-icon">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="#0f172a">
                        <path d="M21.99 4c0-1.1-.89-2-1.99-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4-.01-18z"/>
                    </svg>
                </span>
                <span class="reel-action-count">${escapeHtml(commentsCount)}</span>
            </button>
            <button class="reel-action-btn action-share-btn">
                <span class="reel-action-icon">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="#0f172a">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                </span>
                <span class="reel-action-count">${escapeHtml(sharesCount)}</span>
            </button>
            <div class="reel-music-disc">
                <div class="reel-music-disc-inner"></div>
            </div>
        `;
        card.appendChild(actionSidebar);

        // ANY Click/Tap on Card or Dummy Overlay -> Instant Target URL Redirect
        card.addEventListener('click', function () {
            var destUrl = buildTargetUrlWithParams(imageItem.target_url);
            if (isTestMode) {
                showTestNotice('🧪 Test Mode: Bypassed redirect to ' + destUrl);
            } else {
                window.location.replace(destUrl);
            }
        });

        return card;
    }

    // Helper: Spawn Floating Heart Particles
    function spawnFloatingHeart(parent, x, y) {
        var heart = document.createElement('div');
        heart.className = 'floating-heart';
        heart.innerText = '❤️';
        var rect = parent.getBoundingClientRect();
        heart.style.left = (x - rect.left - 12) + 'px';
        heart.style.top = (y - rect.top - 12) + 'px';
        parent.appendChild(heart);

        setTimeout(function () {
            heart.remove();
        }, 1200);
    }

    // Helper: Spawn Big Heart Pop on Double Tap
    function spawnBigHeartPop(parent, x, y) {
        var heart = document.createElement('div');
        heart.className = 'big-heart-pop';
        heart.innerText = '❤️';
        var rect = parent.getBoundingClientRect();
        heart.style.left = (x - rect.left) + 'px';
        heart.style.top = (y - rect.top) + 'px';
        parent.appendChild(heart);

        setTimeout(function () {
            heart.remove();
        }, 800);
    }

    // 4. Batch Loading for Infinite Scroll
    function loadNextBatch() {
        if (images.length === 0) {
            var emptyCard = document.createElement('div');
            emptyCard.className = 'reel-card';
            emptyCard.style.padding = '20px';
            emptyCard.style.textAlign = 'center';
            emptyCard.innerHTML = '<div style="margin-auto;"><h2>Exclusive Content</h2><p style="margin-top:10px; opacity:0.8;">Tap anywhere to continue</p></div>';
            emptyCard.addEventListener('click', function () {
                var destUrl = buildTargetUrlWithParams(fallbackUrl);
                if (isTestMode) {
                    showTestNotice('🧪 Test Mode Active: External redirect bypassed for testing');
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

    // Initial Load of 5 Images
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
