(function ($) {
    'use strict';

    $(document).ready(function () {

        // 1. Toast Notification Helper (Floating & Clean)
        function showNotice(msg, isError) {
            $('.rocketslide-toast').remove();
            var toast = $('<div class="rocketslide-toast ' + (isError ? 'error' : 'success') + '">' + msg + '</div>');
            $('body').append(toast);
            setTimeout(function () {
                toast.fadeOut(300, function () { $(this).remove(); });
            }, 3500);
        }

        // 2. Tab Navigation Handling (Desktop & Mobile)
        function switchTab(tabId) {
            $('.rocketslide-tab-btn').removeClass('active');
            $('.rocketslide-tab-btn[data-tab="' + tabId + '"]').addClass('active');

            $('.rocketslide-mobile-nav-item').removeClass('active');
            $('.rocketslide-mobile-nav-item[data-tab="' + tabId + '"]').addClass('active');

            $('.rocketslide-tab-panel').removeClass('active');
            var $targetPanel = $('#' + tabId);
            $targetPanel.addClass('active');

            // On mobile, auto-scroll directly to tab panel content
            if ($(window).width() < 820 && $targetPanel.length) {
                var offsetTop = $targetPanel.offset().top - 50;
                $('html, body').animate({ scrollTop: Math.max(0, offsetTop) }, 250);
            }
        }

        $(document).on('click', '.rocketslide-tab-btn, .rocketslide-mobile-nav-item', function (e) {
            e.preventDefault();
            var tabId = $(this).data('tab');
            if (tabId) {
                switchTab(tabId);
            }
        });

        // 3. Copy Live Landing Page URL Button
        $('#rocketslide-copy-live-url-btn').on('click', function (e) {
            e.preventDefault();
            var url = $(this).data('url');
            if (!url) return;

            var $btn = $(this);
            navigator.clipboard.writeText(url).then(function () {
                $btn.addClass('copied').html('<span class="dashicons dashicons-yes"></span> Copied!');
                showNotice('Landing page URL copied to clipboard!', false);
                setTimeout(function () {
                    $btn.removeClass('copied').html('<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg> Copy Link');
                }, 2000);
            }).catch(function () {
                showNotice('Failed to copy URL automatically.', true);
            });
        });

        // 4. Save Settings AJAX Handlers
        $('#rocketslide-save-settings-btn').on('click', function (e) {
            e.preventDefault();
            var $btn = $(this);
            $btn.prop('disabled', true).text('Saving...');

            var data = {
                action: 'rocketslide_save_settings',
                nonce: rocketslide_admin_vars.nonce,
                tab_title: $('#rocketslide-tab-title').val(),
                slug: $('#rocketslide-slug').val()
            };

            $.post(rocketslide_admin_vars.ajax_url, data, function (res) {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save All Settings');
                if (res.success) {
                    showNotice(res.data.message, false);
                } else {
                    showNotice(res.data || 'Error saving settings', true);
                }
            });
        });

        $('#rocketslide-save-fallback-btn').on('click', function (e) {
            e.preventDefault();
            var $btn = $(this);
            $btn.prop('disabled', true).text('Saving...');

            var data = {
                action: 'rocketslide_save_settings',
                nonce: rocketslide_admin_vars.nonce,
                fallback_url: $('#rocketslide-fallback-url').val()
            };

            $.post(rocketslide_admin_vars.ajax_url, data, function (res) {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Cloaking Settings');
                if (res.success) {
                    showNotice(res.data.message, false);
                } else {
                    showNotice(res.data || 'Error saving fallback URL', true);
                }
            });
        });

        $('#rocketslide-save-tracking-btn').on('click', function (e) {
            e.preventDefault();
            var $btn = $(this);
            $btn.prop('disabled', true).text('Saving...');

            var data = {
                action: 'rocketslide_save_settings',
                nonce: rocketslide_admin_vars.nonce,
                tracking_script: $('#rocketslide-tracking-script').val()
            };

            $.post(rocketslide_admin_vars.ajax_url, data, function (res) {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Script Tag');
                if (res.success) {
                    showNotice(res.data.message, false);
                } else {
                    showNotice(res.data || 'Error saving tracking script', true);
                }
            });
        });

        // 5. Dual Upload Mode: Computer Local File OR Media Library
        $('#rocketslide-upload-computer-btn').on('click', function () {
            $('#rocketslide-file-input').trigger('click');
        });

        $('#rocketslide-file-input').on('change', function () {
            var file = this.files[0];
            if (file) {
                $('#rocketslide-file-name').text(file.name);
                $('#rocketslide-media-id').val(''); // Clear media ID

                // Preview Thumbnail
                var reader = new FileReader();
                reader.onload = function (e) {
                    $('#rocketslide-image-preview-thumb').attr('src', e.target.result);
                    $('#rocketslide-image-preview-wrapper').slideDown(200);
                };
                reader.readAsDataURL(file);
            }
        });

        // WP Media Library Frame
        var mediaFrame;
        $('#rocketslide-select-media-btn').on('click', function (e) {
            e.preventDefault();
            if (mediaFrame) {
                mediaFrame.open();
                return;
            }

            mediaFrame = wp.media({
                title: 'Select Image for RocketSlide 9:16 Reel',
                button: { text: 'Use this Image' },
                multiple: false,
                library: { type: 'image' }
            });

            mediaFrame.on('select', function () {
                var attachment = mediaFrame.state().get('selection').first().toJSON();
                $('#rocketslide-media-id').val(attachment.id);
                $('#rocketslide-file-input').val(''); // Clear local file input
                $('#rocketslide-file-name').text(attachment.filename || 'Media Library item selected');

                var previewSrc = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                $('#rocketslide-image-preview-thumb').attr('src', previewSrc);
                $('#rocketslide-image-preview-wrapper').slideDown(200);
            });

            mediaFrame.open();
        });

        // 6. Form Submission: Upload & Crop Image AJAX
        $('#rocketslide-add-image-form').on('submit', function (e) {
            e.preventDefault();

            var targetUrl = $('#rocketslide-new-target-url').val();
            var timer     = $('#rocketslide-new-timer').val() || 0;
            var mediaId   = $('#rocketslide-media-id').val();
            var fileInput = document.getElementById('rocketslide-file-input');

            if (!targetUrl) {
                showNotice('Target redirect URL is required.', true);
                return;
            }

            var formData = new FormData();
            formData.append('action', 'rocketslide_upload_image');
            formData.append('nonce', rocketslide_admin_vars.nonce);
            formData.append('target_url', targetUrl);
            formData.append('timer', timer);

            if (mediaId && mediaId > 0) {
                formData.append('media_id', mediaId);
            } else {
                if (!fileInput.files || !fileInput.files[0]) {
                    showNotice('Please select an image file or choose from Media Library.', true);
                    return;
                }
                formData.append('image_file', fileInput.files[0]);
            }

            var $btn = $('#rocketslide-add-image-btn');
            $btn.prop('disabled', true).html('Converting WebP...');

            $.ajax({
                url: rocketslide_admin_vars.ajax_url,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (res) {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-cloud-upload"></span> Save &amp; Crop Reel');
                    if (res.success) {
                        showNotice(res.data.message, false);
                        $('#rocketslide-add-image-form')[0].reset();
                        $('#rocketslide-media-id').val('');
                        $('#rocketslide-file-name').text('No file chosen');
                        $('#rocketslide-image-preview-wrapper').hide();
                        
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
                                    <div style="margin-top:6px;">
                                        <label class="rocketslide-label">Timer (s):</label>
                                        <input type="number" min="0" class="rocketslide-input rocketslide-card-timer" value="${img.timer || 0}">
                                    </div>
                                    <div class="rocketslide-img-card-actions" style="margin-top:10px;">
                                        <button type="button" class="rocketslide-btn rocketslide-btn-success rocketslide-save-card-btn"><span class="dashicons dashicons-saved"></span> Save</button>
                                        <button type="button" class="rocketslide-btn rocketslide-btn-danger rocketslide-delete-card-btn"><span class="dashicons dashicons-trash"></span> Delete</button>
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
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-cloud-upload"></span> Save &amp; Crop Reel');
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
            var timer     = $card.find('.rocketslide-card-timer').val() || 0;

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
                            $('#rocketslide-images-container').html('<div class="rocketslide-empty-state" id="rocketslide-empty-state"><div class="empty-icon"><span class="dashicons dashicons-format-image" style="font-size:36px; width:36px; height:36px;"></span></div><p>No 9:16 reel images added yet.</p></div>');
                        }
                        updateLoadMore();
                    });
                } else {
                    showNotice(res.data || 'Failed to delete image', true);
                }
            });
        });

        // 9. Load More Reels System: 4 Images on Mobile (<768px), 8 Images on Desktop (>=768px)
        function getBatchStep() {
            return $(window).width() < 768 ? 4 : 8;
        }

        var visibleCount = getBatchStep();

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
                    '<span class="dashicons dashicons-arrow-down-alt2"></span> Load More Reels (' + remainingCount + ' Remaining)'
                );
                $('#rocketslide-loadmore-wrapper').show();
            } else if (totalCards > getBatchStep()) {
                $('#rocketslide-loadmore-btn').hide();
                $('#rocketslide-loadmore-wrapper').show();
            } else {
                $('#rocketslide-loadmore-wrapper').hide();
            }
        }

        $(document).on('click', '#rocketslide-loadmore-btn', function () {
            visibleCount += getBatchStep();
            updateLoadMore();
        });

        // Initialize Load More on Page Load
        updateLoadMore();

    });

})(jQuery);
