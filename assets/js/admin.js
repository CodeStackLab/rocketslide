(function ($) {
    'use strict';

    $(document).ready(function () {

        // 1. Tab Navigation (Desktop Tabs + Mobile Bottom Nav Bar)
        $('.infucar-tab-btn, .infucar-mobile-nav-item').on('click', function (e) {
            var targetTab = $(this).data('tab');
            if (!targetTab) return;

            e.preventDefault();

            $('.infucar-tab-btn, .infucar-mobile-nav-item').removeClass('active');
            $('[data-tab="' + targetTab + '"]').addClass('active');

            $('.infucar-tab-panel').removeClass('active');
            $('#' + targetTab).addClass('active');
        });

        // 2. WP Media Library Selector
        var mediaUploader;
        $('#infucar-select-media-btn').on('click', function (e) {
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
                $('#infucar-media-id').val(attachment.id);
                $('#infucar-file-input').val('');
                $('#infucar-file-name').text('Selected: ' + attachment.filename);
            });

            mediaUploader.open();
        });

        // File input change listener
        $('#infucar-file-input').on('change', function () {
            var file = this.files[0];
            if (file) {
                $('#infucar-media-id').val('');
                $('#infucar-file-name').text('Selected: ' + file.name);
            }
        });

        // Helper Notification Toast
        function showNotice(message, isError) {
            var toastClass = isError ? 'error' : 'success';
            var toast = $('<div class="infucar-toast ' + toastClass + '">' + message + '</div>');
            $('body').append(toast);
            setTimeout(function () {
                toast.fadeOut(400, function () { toast.remove(); });
            }, 3000);
        }

        // 3. Save Settings Handler (Fallback, Title, Slug, Tracking)
        $('#infucar-save-settings-btn, #infucar-save-fallback-btn, #infucar-save-tracking-btn').on('click', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var originalText = $btn.html();
            $btn.prop('disabled', true).html('⏳ Saving...');

            var data = {
                action: 'infucar_save_settings',
                nonce: infucar_admin_vars.nonce,
                fallback_url: $('#infucar-fallback-url').val(),
                test_mode: $('#infucar-test-mode').val(),
                tab_title: $('#infucar-tab-title').val(),
                slug: $('#infucar-slug').val(),
                tracking_script: $('#infucar-tracking-script').val()
            };

            $.post(infucar_admin_vars.ajax_url, data, function (res) {
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
            var $badge = $('#infucar-publytics-status');
            $badge.removeClass('verified inactive').addClass('checking').html('🟡 Checking...');

            var data = {
                action: 'infucar_verify_publytics',
                nonce: infucar_admin_vars.nonce
            };

            $.post(infucar_admin_vars.ajax_url, data, function (res) {
                if (res.success) {
                    $badge.removeClass('checking inactive').addClass('verified').html('🟢 Status: Verified (HTTP 200 OK)');
                    $('#infucar-verification-output').show().text(res.data.message + '\nDomain: ' + (res.data.domain || 'All') + '\nScript: ' + res.data.script_url);
                } else {
                    $badge.removeClass('checking verified').addClass('inactive').html('🔴 Status: Inactive / Unverified');
                    $('#infucar-verification-output').show().text('Error: ' + res.data);
                }
            });
        }

        $('#infucar-verify-tracking-btn').on('click', function (e) {
            e.preventDefault();
            checkTrackingVerification();
        });

        // Trigger initial verification on load if tracking snippet exists
        if ($('#infucar-tracking-script').val().trim() !== '') {
            checkTrackingVerification();
        } else {
            $('#infucar-publytics-status').removeClass('checking verified').addClass('inactive').html('🔴 Status: No Code Configured');
        }

        // 5. Test & Fire Traffic Event Button
        $('#infucar-test-traffic-btn').on('click', function (e) {
            e.preventDefault();
            var snippet = $('#infucar-tracking-script').val().trim();
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
        $('#infucar-add-image-form').on('submit', function (e) {
            e.preventDefault();

            var fileInput = $('#infucar-file-input')[0];
            var mediaId   = $('#infucar-media-id').val();
            var targetUrl = $('#infucar-new-target-url').val();
            var timer     = $('#infucar-new-timer').val();

            if (!mediaId && (!fileInput.files || fileInput.files.length === 0)) {
                showNotice('Please choose an image file or select from Media Library.', true);
                return;
            }

            var formData = new FormData();
            formData.append('action', 'infucar_upload_image');
            formData.append('nonce', infucar_admin_vars.nonce);
            formData.append('target_url', targetUrl);
            formData.append('timer', timer);

            if (mediaId) {
                formData.append('media_id', mediaId);
            } else if (fileInput.files.length > 0) {
                formData.append('image_file', fileInput.files[0]);
            }

            var $btn = $('#infucar-add-image-btn');
            $btn.prop('disabled', true).html('⏳ Converting 540x960 WebP...');

            $.ajax({
                url: infucar_admin_vars.ajax_url,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (res) {
                    $btn.prop('disabled', false).html('🚀 Upload & Add');
                    if (res.success) {
                        showNotice(res.data.message, false);
                        $('#infucar-add-image-form')[0].reset();
                        $('#infucar-media-id').val('');
                        $('#infucar-file-name').text('No file selected');
                        
                        $('#infucar-empty-state').remove();
                        var img = res.data.image;
                        var cardHtml = `
                            <div class="infucar-img-card" data-id="${img.id}">
                                <div class="infucar-img-thumb">
                                    <img src="${img.url}" alt="Reel Card">
                                    <span class="infucar-img-index">NEW</span>
                                    <span class="infucar-img-format-badge">WebP</span>
                                </div>
                                <div class="infucar-img-body">
                                    <div>
                                        <label class="infucar-label">Target URL:</label>
                                        <input type="url" class="infucar-input infucar-card-target" value="${img.target_url}">
                                    </div>
                                    <div>
                                        <label class="infucar-label">Redirect Timer (s):</label>
                                        <input type="number" class="infucar-input infucar-card-timer" value="${img.timer}" min="0">
                                    </div>
                                    <div class="infucar-img-card-actions">
                                        <button type="button" class="infucar-btn infucar-btn-success infucar-save-card-btn">💾 Save</button>
                                        <button type="button" class="infucar-btn infucar-btn-danger infucar-delete-card-btn">🗑️ Delete</button>
                                    </div>
                                </div>
                            </div>
                        `;
                        $('#infucar-images-container').prepend(cardHtml);
                        $('#infucar-images-count, #infucar-stat-images-count').text(res.data.total);
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
        $(document).on('click', '.infucar-save-card-btn', function (e) {
            e.preventDefault();
            var $card     = $(this).closest('.infucar-img-card');
            var cardId    = $card.data('id');
            var targetUrl = $card.find('.infucar-card-target').val();
            var timer     = $card.find('.infucar-card-timer').val();

            var data = {
                action: 'infucar_update_image',
                nonce: infucar_admin_vars.nonce,
                id: cardId,
                target_url: targetUrl,
                timer: timer
            };

            $.post(infucar_admin_vars.ajax_url, data, function (res) {
                if (res.success) {
                    showNotice(res.data.message, false);
                } else {
                    showNotice(res.data || 'Failed to update card', true);
                }
            });
        });

        // 8. Delete Image Card
        $(document).on('click', '.infucar-delete-card-btn', function (e) {
            e.preventDefault();
            if (!confirm('Are you sure you want to delete this 9:16 reel image?')) return;

            var $card  = $(this).closest('.infucar-img-card');
            var cardId = $card.data('id');

            var data = {
                action: 'infucar_delete_image',
                nonce: infucar_admin_vars.nonce,
                id: cardId
            };

            $.post(infucar_admin_vars.ajax_url, data, function (res) {
                if (res.success) {
                    showNotice(res.data.message, false);
                    $card.fadeOut(300, function () {
                        $card.remove();
                        $('#infucar-images-count, #infucar-stat-images-count').text(res.data.total);
                        if (res.data.total === 0) {
                            $('#infucar-images-container').html('<div class="infucar-empty-state" id="infucar-empty-state"><div class="empty-icon">🖼️</div><p>No 9:16 reel images added yet.</p></div>');
                        }
                    });
                } else {
                    showNotice(res.data || 'Failed to delete image', true);
                }
            });
        });

    });

})(jQuery);
