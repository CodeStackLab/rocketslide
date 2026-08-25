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
        toast.style.cssText = 'position:fixed; bottom:20px; left:50%; transform:translateX(-50%); background:rgba(15,23,42,0.92); border:1px solid #38bdf8; color:#38bdf8; padding:10px 20px; border-radius:30px; font-size:12px; font-weight:600; z-index:99999; text-align:center; box-shadow:0 4px 20px rgba(0,0,0,0.5); pointer-events:none; backdrop-filter:blur(6px);';
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
            ? `<img src="${userAvatar}" class="reel-avatar-img" alt="Avatar" style="width:28px; height:28px;">` 
            : `<div class="reel-avatar-placeholder" style="width:28px; height:28px; font-size:14px;">👤</div>`;

        var captionMarkup = caption ? `<div class="reel-caption">${escapeHtml(caption)}</div>` : '';

        infoBlock.innerHTML = `
            <div class="reel-user-row">
                ${avatarMarkup}
                <span class="reel-username">${escapeHtml(username)}</span>
                <button class="reel-follow-pill">Follow</button>
            </div>
            ${captionMarkup}
            <div class="reel-music-row">
                <span>🎵 Original Audio - ${escapeHtml(username)} &nbsp;&nbsp;&nbsp;&nbsp; 🎵 Original Audio - ${escapeHtml(username)}</span>
            </div>
        `;
        card.appendChild(infoBlock);

        // Right Sidebar Social Interaction Bar
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
                <span class="reel-action-icon">🤍</span>
                <span class="reel-action-count count-likes">${escapeHtml(likesCount)}</span>
            </button>
            <button class="reel-action-btn action-comment-btn">
                <span class="reel-action-icon">💬</span>
                <span class="reel-action-count">${escapeHtml(commentsCount)}</span>
            </button>
            <button class="reel-action-btn action-share-btn">
                <span class="reel-action-icon" style="display:flex; align-items:center; justify-content:center;">
                    <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor" style="display:block; margin:0 auto; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));">
                        <path d="M14 4v4.5C7 9.5 4 14.5 3 19.5c2.5-3.5 6-5.1 11-5.1V19l8-7.5L14 4z"/>
                    </svg>
                </span>
                <span class="reel-action-count">${escapeHtml(sharesCount)}</span>
            </button>
            <div class="reel-music-disc">
                <div class="reel-music-disc-inner"></div>
            </div>
        `;
        card.appendChild(actionSidebar);

        // INTERACTION HANDLERS (Stop Propagation so clicking buttons doesn't trigger instant redirect)

        // 1. Follow Buttons Toggle
        var followPill = infoBlock.querySelector('.reel-follow-pill');
        var followPlus = actionSidebar.querySelector('.reel-follow-plus');

        function toggleFollow(e) {
            e.stopPropagation();
            if (followPill.classList.contains('following')) {
                followPill.classList.remove('following');
                followPill.innerText = 'Follow';
                followPlus.classList.remove('followed');
                followPlus.innerText = '+';
            } else {
                followPill.classList.add('following');
                followPill.innerText = 'Following';
                followPlus.classList.add('followed');
                followPlus.innerText = '✓';
            }
        }
        followPill.addEventListener('click', toggleFollow);
        followPlus.addEventListener('click', toggleFollow);

        // 2. Like Button Toggle & Particle Animation
        var likeBtn = actionSidebar.querySelector('.action-like-btn');
        var likeIcon = likeBtn.querySelector('.reel-action-icon');

        likeBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (likeBtn.classList.contains('liked')) {
                likeBtn.classList.remove('liked');
                likeIcon.innerText = '🤍';
            } else {
                likeBtn.classList.add('liked');
                likeIcon.innerText = '❤️';
                spawnFloatingHeart(card, e.clientX, e.clientY);
            }
        });

        // 3. Comment Button -> Opens Comments Drawer
        var commentBtn = actionSidebar.querySelector('.action-comment-btn');
        commentBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            document.getElementById('comments-title').innerText = commentsCount + ' Comments';
            commentsDrawer.classList.add('open');
        });

        // 4. Share Button -> Native Web Share or Clipboard Fallback
        var shareBtn = actionSidebar.querySelector('.action-share-btn');
        shareBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            var shareUrl = window.location.href;
            if (navigator.share) {
                navigator.share({
                    title: document.title,
                    url: shareUrl
                }).catch(function () {});
            } else if (navigator.clipboard) {
                navigator.clipboard.writeText(shareUrl).then(function () {
                    showTestNotice('📋 Link copied to clipboard!');
                }).catch(function () {
                    showTestNotice('📋 Link copied to clipboard!');
                });
            } else {
                showTestNotice('📋 Link copied to clipboard!');
            }
        });

        // 5. Double Tap Gesture on Card Image -> Big Heart Pop Animation
        var lastTap = 0;
        card.addEventListener('click', function (e) {
            // Check if click was on interactive buttons
            if (e.target.closest('.reel-action-bar') || e.target.closest('.reel-info-block')) {
                return;
            }

            var currentTime = new Date().getTime();
            var tapLength = currentTime - lastTap;
            if (tapLength < 300 && tapLength > 0) {
                // Double tap detected!
                e.stopPropagation();
                spawnBigHeartPop(card, e.clientX, e.clientY);
                if (!likeBtn.classList.contains('liked')) {
                    likeBtn.classList.add('liked');
                    likeIcon.innerText = '❤️';
                }
                lastTap = 0;
                return;
            }
            lastTap = currentTime;

            // Single Tap -> Trigger Target Redirect
            setTimeout(function () {
                if (lastTap !== 0) {
                    var destUrl = buildTargetUrlWithParams(imageItem.target_url);
                    if (isTestMode) {
                        showTestNotice('🧪 Test Mode Active: External redirect bypassed for testing');
                    } else {
                        window.location.replace(destUrl);
                    }
                }
            }, 300);
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
