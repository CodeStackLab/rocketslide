(function ($) {
    'use strict';

    $(document).ready(function () {

        // 1. Tab Navigation (Desktop Tabs + Mobile Bottom Nav Bar)
        $('.rocketslide-tab-btn, .rocketslide-mobile-nav-item').on('click', function (e) {
            var targetTab = $(this).data('tab');
            if (!targetTab) return;

            e.preventDefault();

            $('.rocketslide-tab-btn, .rocketslide-mobile-nav-item').removeClass('active');
            $('[data-tab="' + targetTab + '"]').addClass('active');

            $('.rocketslide-tab-panel').removeClass('active');
            $('#' + targetTab).addClass('active');
        });

        // 2. Direct Computer File Picker & WP Media Gallery Selector
        $('#rocketslide-upload-computer-btn').on('click', function (e) {
            e.preventDefault();
            $('#rocketslide-file-input').trigger('click');
        });

        var mediaUploader;

        function openMediaModal() {
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            mediaUploader = wp.media({
                title: '📁 Select or Upload 9:16 Reel Image (Local File or Media Gallery)',
                button: { text: 'Select & Auto-Crop to 9:16 WebP' },
                multiple: false
            });

            mediaUploader.on('select', function () {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#rocketslide-media-id').val(attachment.id);
                $('#rocketslide-file-input').val('');
                $('#rocketslide-file-name').html('✅ Gallery: <strong style="color:var(--blue);">' + (attachment.filename || attachment.title || 'Selected Image') + '</strong>');

                // Render Live Thumbnail Preview
                if (attachment.url) {
                    $('#rocketslide-image-preview-thumb').attr('src', attachment.url);
                    $('#rocketslide-image-preview-wrapper').css('display', 'flex');
                }
            });

            mediaUploader.open();
        }

        $('#rocketslide-select-media-btn, #rocketslide-file-name').on('click', function (e) {
            e.preventDefault();
            openMediaModal();
        });

        // File input change listener (Computer Upload)
        $('#rocketslide-file-input').on('change', function () {
            var file = this.files[0];
            if (file) {
                $('#rocketslide-media-id').val('');
                $('#rocketslide-file-name').html('✅ PC File: <strong style="color:var(--blue);">' + file.name + '</strong>');

                // Render Local Computer Image Preview
                var reader = new FileReader();
                reader.onload = function (e) {
                    $('#rocketslide-image-preview-thumb').attr('src', e.target.result);
                    $('#rocketslide-image-preview-wrapper').css('display', 'flex');
                };
                reader.readAsDataURL(file);
            }
        });

        // Helper Notification Toast
        function showNotice(message, isError) {
            var toastClass = isError ? 'error' : 'success';
            var toast = $('<div class="rocketslide-toast ' + toastClass + '">' + message + '</div>');
            $('body').append(toast);
            setTimeout(function () {
                toast.fadeOut(400, function () { toast.remove(); });
            }, 3000);
        }

        // 3. Save Settings Handler (Fallback, Title, Slug, Tracking)
        $('#rocketslide-save-settings-btn, #rocketslide-save-fallback-btn, #rocketslide-save-tracking-btn').on('click', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var originalText = $btn.html();
            $btn.prop('disabled', true).html('⏳ Saving...');

            var data = {
                action: 'rocketslide_save_settings',
                nonce: rocketslide_admin_vars.nonce,
                fallback_url: $('#rocketslide-fallback-url').val(),
                test_mode: $('#rocketslide-test-mode').val(),
                tab_title: $('#rocketslide-tab-title').val(),
                slug: $('#rocketslide-slug').val(),
                tracking_script: $('#rocketslide-tracking-script').val()
            };

            $.post(rocketslide_admin_vars.ajax_url, data, function (res) {
                $btn.prop('disabled', false).html(originalText);
                if (res.success) {
                    showNotice(res.data.message, false);
                    checkTrackingVerification();
                } else {
                    showNotice(res.data.data || 'Failed to save settings', true);
                }
            });
        });

        // 4. Verify Publytics / Tracking Script Engine
        function checkTrackingVerification() {
            var $badge = $('#rocketslide-publytics-status');
            $badge.removeClass('verified inactive').addClass('checking').html('🟡 Checking...');

            var data = {
                action: 'rocketslide_verify_publytics',
                nonce: rocketslide_admin_vars.nonce
            };

            $.post(rocketslide_admin_vars.ajax_url, data, function (res) {
                if (res.success) {
                    $badge.removeClass('checking inactive').addClass('verified').html('🟢 Status: Verified (HTTP 200 OK)');
                    $('#rocketslide-verification-output').show().text(res.data.message + '\nDomain: ' + (res.data.domain || 'All') + '\nScript: ' + res.data.script_url);
                } else {
                    $badge.removeClass('checking verified').addClass('inactive').html('🔴 Status: Inactive / Unverified');
                    $('#rocketslide-verification-output').show().text('Error: ' + res.data);
                }
            });
        }

        $('#rocketslide-verify-tracking-btn').on('click', function (e) {
            e.preventDefault();
            checkTrackingVerification();
        });

        // Trigger initial verification on load if tracking snippet exists
        if ($('#rocketslide-tracking-script').val().trim() !== '') {
            checkTrackingVerification();
        }

        // 5. Fire Live Test Traffic Analytics Event
        $('#rocketslide-test-traffic-btn').on('click', function (e) {
            e.preventDefault();
            var scriptVal = $('#rocketslide-tracking-script').val().trim();
            if (!scriptVal) {
                showNotice('Please enter a tracking script first', true);
                return;
            }

            try {
                var parser = new DOMParser();
                var doc = parser.parseFromString(scriptVal, 'text/html');
                var scriptEl = doc.querySelector('script');

                if (scriptEl) {
                    var newScript = document.createElement('script');
                    Array.from(scriptEl.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                    newScript.innerHTML = scriptEl.innerHTML;
                    document.head.appendChild(newScript);

                    newScript.onload = function () {
                        if (typeof window.publytics === 'function') {
                            window.publytics('pageview');
                        }
                        showNotice('🚀 Live Analytics Event Fired Successfully!', false);
                    };
                } else {
                    showNotice('No valid <script> tag found in snippet', true);
                }
            } catch (err) {
                showNotice('Error firing event: ' + err.message, true);
            }
        });

        // Avatar WP Media Picker for New Reel Form
        var avatarUploader;
        $('#rocketslide-select-avatar-btn').on('click', function (e) {
            e.preventDefault();
            if (avatarUploader) {
                avatarUploader.open();
                return;
            }
            avatarUploader = wp.media({
                title: '👤 Choose User Profile Avatar',
                button: { text: 'Use Avatar Image' },
                multiple: false
            });
            avatarUploader.on('select', function () {
                var attachment = avatarUploader.state().get('selection').first().toJSON();
                $('#rocketslide-new-user-avatar').val(attachment.url);
            });
            avatarUploader.open();
        });

        // Local Computer Avatar Upload for New Reel Form
        $('#rocketslide-upload-avatar-computer-btn').on('click', function (e) {
            e.preventDefault();
            $('#rocketslide-new-avatar-file-input').trigger('click');
        });

        $('#rocketslide-new-avatar-file-input').on('change', function () {
            var files = this.files;
            if (!files || files.length === 0) return;

            var formData = new FormData();
            formData.append('action', 'rocketslide_upload_avatar');
            formData.append('nonce', rocketslide_admin_vars.nonce);
            formData.append('avatar_file', files[0]);

            showNotice('Uploading avatar image...', false);

            $.ajax({
                url: rocketslide_admin_vars.ajax_url,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (res) {
                    if (res.success) {
                        $('#rocketslide-new-user-avatar').val(res.data.url);
                        showNotice('👤 Avatar image uploaded successfully!', false);
                    } else {
                        showNotice(res.data || 'Avatar upload failed.', true);
                    }
                },
                error: function () {
                    showNotice('Server error uploading avatar.', true);
                }
            });
        });

        // Avatar WP Media Picker for Individual Cards
        $(document).on('click', '.rocketslide-pick-card-avatar-btn', function (e) {
            e.preventDefault();
            var $input = $(this).siblings('.rocketslide-card-avatar');
            var cardAvatarPicker = wp.media({
                title: '👤 Choose User Profile Avatar',
                button: { text: 'Use Avatar Image' },
                multiple: false
            });
            cardAvatarPicker.on('select', function () {
                var attachment = cardAvatarPicker.state().get('selection').first().toJSON();
                $input.val(attachment.url);
            });
            cardAvatarPicker.open();
        });

        // Local Computer Avatar Upload for Individual Cards
        $(document).on('click', '.rocketslide-pick-card-avatar-computer-btn', function (e) {
            e.preventDefault();
            var $fileInput = $(this).siblings('.rocketslide-card-avatar-file-input');
            $fileInput.trigger('click');
        });

        $(document).on('change', '.rocketslide-card-avatar-file-input', function () {
            var files = this.files;
            if (!files || files.length === 0) return;

            var $urlInput = $(this).siblings('.rocketslide-card-avatar');
            var formData = new FormData();
            formData.append('action', 'rocketslide_upload_avatar');
            formData.append('nonce', rocketslide_admin_vars.nonce);
            formData.append('avatar_file', files[0]);

            showNotice('Uploading avatar image...', false);

            $.ajax({
                url: rocketslide_admin_vars.ajax_url,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (res) {
                    if (res.success) {
                        $urlInput.val(res.data.url);
                        showNotice('👤 Card avatar uploaded successfully!', false);
                    } else {
                        showNotice(res.data || 'Avatar upload failed.', true);
                    }
                },
                error: function () {
                    showNotice('Server error uploading avatar.', true);
                }
            });
        });

        // 6. Upload & Process Image Form
        $('#rocketslide-add-image-form').on('submit', function (e) {
            e.preventDefault();

            var fileInput      = $('#rocketslide-file-input')[0];
            var mediaId        = $('#rocketslide-media-id').val();
            var targetUrl      = $('#rocketslide-new-target-url').val();
            var timer          = $('#rocketslide-new-timer').val();
            var username       = $('#rocketslide-new-username').val();
            var userAvatar     = $('#rocketslide-new-user-avatar').val();
            var caption        = $('#rocketslide-new-caption').val();
            var likesCount     = $('#rocketslide-new-likes').val();
            var commentsCount  = $('#rocketslide-new-comments').val();
            var sharesCount    = $('#rocketslide-new-shares').val();

            if (!mediaId && (!fileInput.files || fileInput.files.length === 0)) {
                showNotice('Please select an image file first via "Upload from Computer" or "WP Gallery".', true);
                $('#rocketslide-file-input').trigger('click');
                return;
            }

            var formData = new FormData();
            formData.append('action', 'rocketslide_upload_image');
            formData.append('nonce', rocketslide_admin_vars.nonce);
            formData.append('target_url', targetUrl);
            formData.append('timer', timer);
            formData.append('username', username);
            formData.append('user_avatar', userAvatar);
            formData.append('caption', caption);
            formData.append('likes_count', likesCount);
            formData.append('comments_count', commentsCount);
            formData.append('shares_count', sharesCount);

            if (mediaId) {
                formData.append('media_id', mediaId);
            } else if (fileInput.files.length > 0) {
                formData.append('image_file', fileInput.files[0]);
            }

            var $btn = $('#rocketslide-add-image-btn');
            $btn.prop('disabled', true).html('⏳ Saving & Converting WebP...');

            $.ajax({
                url: rocketslide_admin_vars.ajax_url,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (res) {
                    $btn.prop('disabled', false).html('🚀 Save & Crop New Reel (540×960 WebP)');
                    if (res.success) {
                        showNotice(res.data.message, false);
                        $('#rocketslide-add-image-form')[0].reset();
                        $('#rocketslide-media-id').val('');
                        $('#rocketslide-file-name').text('No file chosen');
                        $('#rocketslide-image-preview-wrapper').hide();
                        
                        $('#rocketslide-empty-state').remove();
                        var img = res.data.image;
                        var avatarHtml = img.user_avatar ? `<img src="${img.user_avatar}" class="thumb-avatar" alt="Avatar">` : `<span class="thumb-avatar-placeholder">👤</span>`;
                        var cardHtml = `
                            <div class="rocketslide-img-card" data-id="${img.id}">
                                <div class="rocketslide-img-thumb">
                                    <img src="${img.url}" alt="Reel Card">
                                    <span class="rocketslide-img-index">NEW</span>
                                    <span class="rocketslide-img-format-badge">WebP</span>
                                    <div class="rocketslide-thumb-user-badge">
                                        ${avatarHtml}
                                        <span>${img.username || '@viral_reels'}</span>
                                    </div>
                                </div>
                                <div class="rocketslide-img-body">
                                    <div>
                                        <label class="rocketslide-label">Target URL:</label>
                                        <input type="url" class="rocketslide-input rocketslide-card-target" value="${img.target_url}">
                                    </div>
                                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px;">
                                        <div>
                                            <label class="rocketslide-label">Username:</label>
                                            <input type="text" class="rocketslide-input rocketslide-card-username" value="${img.username || '@viral_reels'}">
                                        </div>
                                        <div>
                                            <label class="rocketslide-label">Redirect Timer (s):</label>
                                            <input type="number" class="rocketslide-input rocketslide-card-timer" value="${img.timer}" min="0">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="rocketslide-label">Avatar Image URL:</label>
                                        <input type="url" class="rocketslide-input rocketslide-card-avatar" value="${img.user_avatar || ''}" placeholder="Avatar Image URL">
                                    </div>
                                    <div>
                                        <label class="rocketslide-label">Reel Caption:</label>
                                        <input type="text" class="rocketslide-input rocketslide-card-caption" value="${img.caption || ''}">
                                    </div>
                                    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:6px;">
                                        <div>
                                            <label class="rocketslide-label">❤️ Likes:</label>
                                            <input type="text" class="rocketslide-input rocketslide-card-likes" value="${img.likes_count || '142.8K'}">
                                        </div>
                                        <div>
                                            <label class="rocketslide-label">💬 Comments:</label>
                                            <input type="text" class="rocketslide-input rocketslide-card-comments" value="${img.comments_count || '3.4K'}">
                                        </div>
                                        <div>
                                            <label class="rocketslide-label">↗️ Shares:</label>
                                            <input type="text" class="rocketslide-input rocketslide-card-shares" value="${img.shares_count || '18.9K'}">
                                        </div>
                                    </div>
                                    <div class="rocketslide-img-card-actions">
                                        <button type="button" class="rocketslide-btn rocketslide-btn-success rocketslide-save-card-btn">💾 Save</button>
                                        <button type="button" class="rocketslide-btn rocketslide-btn-danger rocketslide-delete-card-btn">🗑️ Delete</button>
                                    </div>
                                </div>
                            </div>
                        `;
                        $('#rocketslide-images-container').prepend(cardHtml);
                        $('#rocketslide-images-count, #rocketslide-stat-images-count').text(res.data.total);
                        updateLoadMore();
                    } else {
                        showNotice(res.data || 'Image upload failed.', true);
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).html('🚀 Upload & Add');
                    showNotice('Server error processing image.', true);
                }
            });
        });

        // 7. Save Individual Image Card Details
        $(document).on('click', '.rocketslide-save-card-btn', function (e) {
            e.preventDefault();
            var $card          = $(this).closest('.rocketslide-img-card');
            var cardId         = $card.data('id');
            var targetUrl      = $card.find('.rocketslide-card-target').val();
            var timer          = $card.find('.rocketslide-card-timer').val();
            var username       = $card.find('.rocketslide-card-username').val();
            var userAvatar     = $card.find('.rocketslide-card-avatar').val();
            var caption        = $card.find('.rocketslide-card-caption').val();
            var likesCount     = $card.find('.rocketslide-card-likes').val();
            var commentsCount  = $card.find('.rocketslide-card-comments').val();
            var sharesCount    = $card.find('.rocketslide-card-shares').val();

            var data = {
                action: 'rocketslide_update_image',
                nonce: rocketslide_admin_vars.nonce,
                id: cardId,
                target_url: targetUrl,
                timer: timer,
                username: username,
                user_avatar: userAvatar,
                caption: caption,
                likes_count: likesCount,
                comments_count: commentsCount,
                shares_count: sharesCount
            };

            $.post(rocketslide_admin_vars.ajax_url, data, function (res) {
                if (res.success) {
                    showNotice(res.data.message, false);
                } else {
                    showNotice(res.data || 'Failed to update card', true);
                }
            });
        });

        // 8. Delete Image Card
        $(document).on('click', '.rocketslide-delete-card-btn', function (e) {
            e.preventDefault();
            if (!confirm('Are you sure you want to delete this 9:16 reel image?')) return;

            var $card  = $(this).closest('.rocketslide-img-card');
            var cardId = $card.data('id');

            var data = {
                action: 'rocketslide_delete_image',
                nonce: rocketslide_admin_vars.nonce,
                id: cardId
            };

            $.post(rocketslide_admin_vars.ajax_url, data, function (res) {
                if (res.success) {
                    showNotice(res.data.message, false);
                    $card.fadeOut(300, function () {
                        $card.remove();
                        $('#rocketslide-images-count, #rocketslide-stat-images-count').text(res.data.total);
                        if (res.data.total === 0) {
                            $('#rocketslide-images-container').html('<div class="rocketslide-empty-state" id="rocketslide-empty-state"><div class="empty-icon">🖼️</div><p>No 9:16 reel images added yet.</p></div>');
                        }
                        updatePagination();
                    });
                } else {
                    showNotice(res.data || 'Failed to delete image', true);
                }
            });
        });

        // 9. Load More Reels System (8 Cards Default, 8 Per Load)
        var visibleCount = 8;
        var stepCount = 8;

        function updateLoadMore() {
            var $cards = $('#rocketslide-images-container .rocketslide-img-card');
            var totalCards = $cards.length;

            if (totalCards === 0) {
                $('#rocketslide-loadmore-wrapper').hide();
                return;
            }

            $cards.each(function (index) {
                if (index < visibleCount) {
                    $(this).css('display', 'flex');
                } else {
                    $(this).css('display', 'none');
                }
            });

            var showingCount = Math.min(visibleCount, totalCards);
            var remainingCount = totalCards - showingCount;

            $('#rocketslide-loadmore-info').text(
                'Showing ' + showingCount + ' of ' + totalCards + ' Reel Cards'
            );

            if (remainingCount > 0) {
                $('#rocketslide-loadmore-btn').show().html(
                    '⬇️ Load More Reels (' + remainingCount + ' Remaining)'
                );
                $('#rocketslide-loadmore-wrapper').show();
            } else if (totalCards > stepCount) {
                $('#rocketslide-loadmore-btn').hide();
                $('#rocketslide-loadmore-wrapper').show();
            } else {
                $('#rocketslide-loadmore-wrapper').hide();
            }
        }

        $(document).on('click', '#rocketslide-loadmore-btn', function () {
            visibleCount += stepCount;
            updateLoadMore();
        });

        // Initialize Load More on Page Load
        updateLoadMore();

    });

})(jQuery);
