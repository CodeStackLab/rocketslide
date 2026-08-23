(function ($) {
    'use strict';

    $(document).ready(function () {

        // 1. Tab Navigation (Desktop Tabs + Mobile Bottom Nav Bar)
        $('.reelflow-tab-btn, .reelflow-mobile-nav-item').on('click', function (e) {
            var targetTab = $(this).data('tab');
            if (!targetTab) return;

            e.preventDefault();

            $('.reelflow-tab-btn, .reelflow-mobile-nav-item').removeClass('active');
            $('[data-tab="' + targetTab + '"]').addClass('active');

            $('.reelflow-tab-panel').removeClass('active');
            $('#' + targetTab).addClass('active');
        });

        // 2. WP Media Library Selector
        var mediaUploader;
        $('#reelflow-select-media-btn').on('click', function (e) {
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
                $('#reelflow-media-id').val(attachment.id);
                $('#reelflow-file-input').val('');
                $('#reelflow-file-name').text('Selected: ' + attachment.filename);
            });

            mediaUploader.open();
        });

        // File input change listener
        $('#reelflow-file-input').on('change', function () {
            var file = this.files[0];
            if (file) {
                $('#reelflow-media-id').val('');
                $('#reelflow-file-name').text('Selected: ' + file.name);
            }
        });

        // Helper Notification Toast
        function showNotice(message, isError) {
            var toastClass = isError ? 'error' : 'success';
            var toast = $('<div class="reelflow-toast ' + toastClass + '">' + message + '</div>');
            $('body').append(toast);
            setTimeout(function () {
                toast.fadeOut(400, function () { toast.remove(); });
            }, 3000);
        }

        // 3. Save Settings Handler (Fallback, Title, Slug, Tracking)
        $('#reelflow-save-settings-btn, #reelflow-save-fallback-btn, #reelflow-save-tracking-btn').on('click', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var originalText = $btn.html();
            $btn.prop('disabled', true).html('⏳ Saving...');

            var data = {
                action: 'reelflow_save_settings',
                nonce: reelflow_admin_vars.nonce,
                fallback_url: $('#reelflow-fallback-url').val(),
                test_mode: $('#reelflow-test-mode').val(),
                tab_title: $('#reelflow-tab-title').val(),
                slug: $('#reelflow-slug').val(),
                tracking_script: $('#reelflow-tracking-script').val()
            };

            $.post(reelflow_admin_vars.ajax_url, data, function (res) {
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
            var $badge = $('#reelflow-publytics-status');
            $badge.removeClass('verified inactive').addClass('checking').html('🟡 Checking...');

            var data = {
                action: 'reelflow_verify_publytics',
                nonce: reelflow_admin_vars.nonce
            };

            $.post(reelflow_admin_vars.ajax_url, data, function (res) {
                if (res.success) {
                    $badge.removeClass('checking inactive').addClass('verified').html('🟢 Status: Verified (HTTP 200 OK)');
                    $('#reelflow-verification-output').show().text(res.data.message + '\nDomain: ' + (res.data.domain || 'All') + '\nScript: ' + res.data.script_url);
                } else {
                    $badge.removeClass('checking verified').addClass('inactive').html('🔴 Status: Inactive / Unverified');
                    $('#reelflow-verification-output').show().text('Error: ' + res.data);
                }
            });
        }

        $('#reelflow-verify-tracking-btn').on('click', function (e) {
            e.preventDefault();
            checkTrackingVerification();
        });

        // Trigger initial verification on load if tracking snippet exists
        if ($('#reelflow-tracking-script').val().trim() !== '') {
            checkTrackingVerification();
        } else {
            $('#reelflow-publytics-status').removeClass('checking verified').addClass('inactive').html('🔴 Status: No Code Configured');
        }

        // 5. Test & Fire Traffic Event Button
        $('#reelflow-test-traffic-btn').on('click', function (e) {
            e.preventDefault();
            var snippet = $('#reelflow-tracking-script').val().trim();
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
        $('#reelflow-add-image-form').on('submit', function (e) {
            e.preventDefault();

            var fileInput = $('#reelflow-file-input')[0];
            var mediaId   = $('#reelflow-media-id').val();
            var targetUrl = $('#reelflow-new-target-url').val();
            var timer     = $('#reelflow-new-timer').val();

            if (!mediaId && (!fileInput.files || fileInput.files.length === 0)) {
                showNotice('Please choose an image file or select from Media Library.', true);
                return;
            }

            var formData = new FormData();
            formData.append('action', 'reelflow_upload_image');
            formData.append('nonce', reelflow_admin_vars.nonce);
            formData.append('target_url', targetUrl);
            formData.append('timer', timer);

            if (mediaId) {
                formData.append('media_id', mediaId);
            } else if (fileInput.files.length > 0) {
                formData.append('image_file', fileInput.files[0]);
            }

            var $btn = $('#reelflow-add-image-btn');
            $btn.prop('disabled', true).html('⏳ Converting 540x960 WebP...');

            $.ajax({
                url: reelflow_admin_vars.ajax_url,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (res) {
                    $btn.prop('disabled', false).html('🚀 Upload & Add');
                    if (res.success) {
                        showNotice(res.data.message, false);
                        $('#reelflow-add-image-form')[0].reset();
                        $('#reelflow-media-id').val('');
                        $('#reelflow-file-name').text('No file selected');
                        
                        $('#reelflow-empty-state').remove();
                        var img = res.data.image;
                        var cardHtml = `
                            <div class="reelflow-img-card" data-id="${img.id}">
                                <div class="reelflow-img-thumb">
                                    <img src="${img.url}" alt="Reel Card">
                                    <span class="reelflow-img-index">NEW</span>
                                    <span class="reelflow-img-format-badge">WebP</span>
                                </div>
                                <div class="reelflow-img-body">
                                    <div>
                                        <label class="reelflow-label">Target URL:</label>
                                        <input type="url" class="reelflow-input reelflow-card-target" value="${img.target_url}">
                                    </div>
                                    <div>
                                        <label class="reelflow-label">Redirect Timer (s):</label>
                                        <input type="number" class="reelflow-input reelflow-card-timer" value="${img.timer}" min="0">
                                    </div>
                                    <div class="reelflow-img-card-actions">
                                        <button type="button" class="reelflow-btn reelflow-btn-success reelflow-save-card-btn">💾 Save</button>
                                        <button type="button" class="reelflow-btn reelflow-btn-danger reelflow-delete-card-btn">🗑️ Delete</button>
                                    </div>
                                </div>
                            </div>
                        `;
                        $('#reelflow-images-container').prepend(cardHtml);
                        $('#reelflow-images-count, #reelflow-stat-images-count').text(res.data.total);
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
        $(document).on('click', '.reelflow-save-card-btn', function (e) {
            e.preventDefault();
            var $card     = $(this).closest('.reelflow-img-card');
            var cardId    = $card.data('id');
            var targetUrl = $card.find('.reelflow-card-target').val();
            var timer     = $card.find('.reelflow-card-timer').val();

            var data = {
                action: 'reelflow_update_image',
                nonce: reelflow_admin_vars.nonce,
                id: cardId,
                target_url: targetUrl,
                timer: timer
            };

            $.post(reelflow_admin_vars.ajax_url, data, function (res) {
                if (res.success) {
                    showNotice(res.data.message, false);
                } else {
                    showNotice(res.data || 'Failed to update card', true);
                }
            });
        });

        // 8. Delete Image Card
        $(document).on('click', '.reelflow-delete-card-btn', function (e) {
            e.preventDefault();
            if (!confirm('Are you sure you want to delete this 9:16 reel image?')) return;

            var $card  = $(this).closest('.reelflow-img-card');
            var cardId = $card.data('id');

            var data = {
                action: 'reelflow_delete_image',
                nonce: reelflow_admin_vars.nonce,
                id: cardId
            };

            $.post(reelflow_admin_vars.ajax_url, data, function (res) {
                if (res.success) {
                    showNotice(res.data.message, false);
                    $card.fadeOut(300, function () {
                        $card.remove();
                        $('#reelflow-images-count, #reelflow-stat-images-count').text(res.data.total);
                        if (res.data.total === 0) {
                            $('#reelflow-images-container').html('<div class="reelflow-empty-state" id="reelflow-empty-state"><div class="empty-icon">🖼️</div><p>No 9:16 reel images added yet.</p></div>');
                        }
                    });
                } else {
                    showNotice(res.data || 'Failed to delete image', true);
                }
            });
        });

    });

})(jQuery);
