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

        // 2. WP Media Library Selector
        var mediaUploader;
        $('#rocketslide-select-media-btn').on('click', function (e) {
            e.preventDefault();
            
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            mediaUploader = wp.media({
                title: 'Select 9:16 Reel Image',
                button: { text: 'Use Selected Image' },
                multiple: false
            });

            mediaUploader.on('select', function () {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#rocketslide-media-id').val(attachment.id);
                $('#rocketslide-file-input').val('');
                $('#rocketslide-file-name').text('Selected: ' + attachment.filename);
            });

            mediaUploader.open();
        });

        // File input change listener
        $('#rocketslide-file-input').on('change', function () {
            var file = this.files[0];
            if (file) {
                $('#rocketslide-media-id').val('');
                $('#rocketslide-file-name').text('Selected: ' + file.name);
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
        } else {
            $('#rocketslide-publytics-status').removeClass('checking verified').addClass('inactive').html('🔴 Status: No Code Configured');
        }

        // 5. Test & Fire Traffic Event Button
        $('#rocketslide-test-traffic-btn').on('click', function (e) {
            e.preventDefault();
            var snippet = $('#rocketslide-tracking-script').val().trim();
            if (!snippet) {
                showNotice('Please paste your tracking snippet first!', true);
                return;
            }

            try {
                var div = document.createElement('div');
                div.innerHTML = snippet;
                var scriptEl = div.querySelector('script');
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

        // 6. Upload & Process Image Form
        $('#rocketslide-add-image-form').on('submit', function (e) {
            e.preventDefault();

            var fileInput = $('#rocketslide-file-input')[0];
            var mediaId   = $('#rocketslide-media-id').val();
            var targetUrl = $('#rocketslide-new-target-url').val();
            var timer     = $('#rocketslide-new-timer').val();

            if (!mediaId && (!fileInput.files || fileInput.files.length === 0)) {
                showNotice('Please choose an image file or select from Media Library.', true);
                return;
            }

            var formData = new FormData();
            formData.append('action', 'rocketslide_upload_image');
            formData.append('nonce', rocketslide_admin_vars.nonce);
            formData.append('target_url', targetUrl);
            formData.append('timer', timer);

            if (mediaId) {
                formData.append('media_id', mediaId);
            } else if (fileInput.files.length > 0) {
                formData.append('image_file', fileInput.files[0]);
            }

            var $btn = $('#rocketslide-add-image-btn');
            $btn.prop('disabled', true).html('⏳ Converting 540x960 WebP...');

            $.ajax({
                url: rocketslide_admin_vars.ajax_url,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (res) {
                    $btn.prop('disabled', false).html('🚀 Upload & Add');
                    if (res.success) {
                        showNotice(res.data.message, false);
                        $('#rocketslide-add-image-form')[0].reset();
                        $('#rocketslide-media-id').val('');
                        $('#rocketslide-file-name').text('No file selected');
                        
                        $('#rocketslide-empty-state').remove();
                        var img = res.data.image;
                        var cardHtml = `
                            <div class="rocketslide-img-card" data-id="${img.id}">
                                <div class="rocketslide-img-thumb">
                                    <img src="${img.url}" alt="Reel Card">
                                    <span class="rocketslide-img-index">NEW</span>
                                    <span class="rocketslide-img-format-badge">WebP</span>
                                </div>
                                <div class="rocketslide-img-body">
                                    <div>
                                        <label class="rocketslide-label">Target URL:</label>
                                        <input type="url" class="rocketslide-input rocketslide-card-target" value="${img.target_url}">
                                    </div>
                                    <div>
                                        <label class="rocketslide-label">Redirect Timer (s):</label>
                                        <input type="number" class="rocketslide-input rocketslide-card-timer" value="${img.timer}" min="0">
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
            var $card     = $(this).closest('.rocketslide-img-card');
            var cardId    = $card.data('id');
            var targetUrl = $card.find('.rocketslide-card-target').val();
            var timer     = $card.find('.rocketslide-card-timer').val();

            var data = {
                action: 'rocketslide_update_image',
                nonce: rocketslide_admin_vars.nonce,
                id: cardId,
                target_url: targetUrl,
                timer: timer
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
                    });
                } else {
                    showNotice(res.data || 'Failed to delete image', true);
                }
            });
        });

    });

})(jQuery);
