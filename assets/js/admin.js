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

        // 4. Dynamic Live Slug Typing Preview
        $('#rocketslide-slug').on('input', function () {
            var rawSlug = $(this).val().toLowerCase().replace(/[^a-z0-9-_]/g, '');
            var baseDomain = $('#rocketslide-base-domain').text().replace(/\/+$/, '') + '/';
            var fullUrl = baseDomain + (rawSlug ? rawSlug + '/' : '');
            $('#rocketslide-slug-live-preview').text(fullUrl);
        });

        // 5. Save Settings AJAX Handlers
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
            $btn.prop('disabled', true).text('Saving Shields...');

            var data = {
                action: 'rocketslide_save_settings',
                nonce: rocketslide_admin_vars.nonce,
                fallback_url: $('#rocketslide-fallback-url').val(),
                test_mode: $('#rocketslide-test-mode').is(':checked') ? '1' : '0',
                bot_protection: $('#rocketslide-bot-protection').is(':checked') ? '1' : '0',
                datacenter_shield: $('#rocketslide-datacenter-shield').is(':checked') ? '1' : '0',
                vpn_shield: $('#rocketslide-vpn-shield').is(':checked') ? '1' : '0',
                headless_shield: $('#rocketslide-headless-shield').is(':checked') ? '1' : '0',
                probe_shield: $('#rocketslide-probe-shield').is(':checked') ? '1' : '0',
                browser_integrity: $('#rocketslide-browser-integrity').is(':checked') ? '1' : '0',
                rate_limit: $('#rocketslide-rate-limit').is(':checked') ? '1' : '0',
                allow_fb_profiles: $('#rocketslide-allow-fb-profiles').is(':checked') ? '1' : '0',
                allow_fb_reels: $('#rocketslide-allow-fb-reels').is(':checked') ? '1' : '0',
                allow_fb_events: $('#rocketslide-allow-fb-events').is(':checked') ? '1' : '0',
                allow_fb_comments: $('#rocketslide-allow-fb-comments').is(':checked') ? '1' : '0',
                allow_fb_groups: $('#rocketslide-allow-fb-groups').is(':checked') ? '1' : '0',
                allow_fb_pages: $('#rocketslide-allow-fb-pages').is(':checked') ? '1' : '0',
                allow_fb_stories: $('#rocketslide-allow-fb-stories').is(':checked') ? '1' : '0',
                block_fb_automated: $('#rocketslide-block-fb-automated').is(':checked') ? '1' : '0',
                country_block_enabled: $('#rocketslide-country-block-enabled').is(':checked') ? '1' : '0',
                blocked_countries: $('#rocketslide-blocked-countries').val(),
                ip_allowlist: $('#rocketslide-ip-allowlist').val(),
                manual_blocked_ips: $('#rocketslide-manual-blocked-ips').val(),
                mask_referrer: $('#rocketslide-mask-referrer').is(':checked') ? '1' : '0',
                inject_social_utm: $('#rocketslide-inject-social-utm').is(':checked') ? '1' : '0',
                social_network_source: $('#rocketslide-social-network-source').val() || 'auto',
                auto_fbclid: $('#rocketslide-auto-fbclid').is(':checked') ? '1' : '0'
            };

            $.post(rocketslide_admin_vars.ajax_url, data, function (res) {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Cloaking &amp; Traffic Shield Settings');
                if (res.success) {
                    showNotice(res.data.message, false);
                    if (res.data.stats) {
                        $('#rocketslide-stat-shields').text(res.data.stats.active_shields + '/' + res.data.stats.total_shields + ' Active');
                    }
                } else {
                    showNotice(res.data || 'Error saving cloaking settings', true);
                }
            });
        });

        // Add Current IP to Allowlist Button
        $('#rs-add-my-ip-btn').on('click', function (e) {
            e.preventDefault();
            var myIp = $(this).data('ip');
            if (!myIp) return;
            var $input = $('#rocketslide-ip-allowlist');
            var current = ($input.val() || '').trim();
            if (current) {
                var ips = current.split(',').map(function (s) { return s.trim(); });
                if (ips.indexOf(myIp) === -1) {
                    ips.push(myIp);
                    $input.val(ips.join(', '));
                    showNotice('Your IP (' + myIp + ') added to whitelist!', false);
                } else {
                    showNotice('Your IP (' + myIp + ') is already in whitelist.', false);
                }
            } else {
                $input.val(myIp);
                showNotice('Your IP (' + myIp + ') added to whitelist!', false);
            }
        });

        // -------------------------------------------------------------
        // Geo-Firewall & Single-Country Blocking Manager
        // -------------------------------------------------------------
        var COUNTRY_FLAGS = {
            'PK': '🇵🇰', 'IN': '🇮🇳', 'BD': '🇧🇩', 'NG': '🇳🇬',
            'EG': '🇪🇬', 'PH': '🇵🇭', 'TW': '🇹🇼', 'ID': '🇮🇩',
            'VN': '🇻🇳', 'BR': '🇧🇷', 'RU': '🇷🇺', 'CN': '🇨🇳',
            'TR': '🇹🇷', 'US': '🇺🇸', 'GB': '🇬🇧', 'CA': '🇨🇦',
            'DE': '🇩🇪', 'FR': '🇫🇷', 'IT': '🇮🇹', 'ES': '🇪🇸',
            'AU': '🇦🇺', 'SA': '🇸🇦', 'AE': '🇦🇪', 'ZA': '🇿🇦',
            'MX': '🇲🇽', 'MY': '🇲🇾', 'TH': '🇹🇭', 'CO': '🇨🇴'
        };

        function getCountryFlag(code) {
            if (!code || code.length !== 2) return '🌐';
            code = code.toUpperCase();
            if (COUNTRY_FLAGS[code]) return COUNTRY_FLAGS[code];
            try {
                return String.fromCodePoint(code.charCodeAt(0) - 65 + 0x1F1E6) +
                       String.fromCodePoint(code.charCodeAt(1) - 65 + 0x1F1E6);
            } catch (e) {
                return '🌐';
            }
        }

        var blockedCountries = new Set();

        // Initialize from existing input
        var initialCountries = ($('#rocketslide-blocked-countries').val() || '')
            .split(',')
            .map(function (c) { return c.trim().toUpperCase(); })
            .filter(function (c) { return c.length === 2; });

        initialCountries.forEach(function (c) {
            blockedCountries.add(c);
        });

        function renderBlockedCountriesTags() {
            var $container = $('#rs-blocked-countries-tags');
            if (!$container.length) return;

            $container.empty();

            var list = Array.from(blockedCountries).sort();
            $('#rs-blocked-count').text(list.length);
            $('#rocketslide-blocked-countries').val(list.join(', '));

            if (list.length === 0) {
                $container.append('<span class="rs-no-countries-placeholder">No countries blocked. All global traffic allowed.</span>');
            } else {
                list.forEach(function (code) {
                    var flag = getCountryFlag(code);
                    var chip = $('<span class="rs-country-chip" data-code="' + code + '">' +
                        flag + ' ' + code + ' ' +
                        '<button type="button" class="rs-remove-country-btn" data-code="' + code + '" title="Remove ' + code + '">&times;</button>' +
                        '</span>');
                    $container.append(chip);
                });
            }

            // Sync quick toggle buttons state
            $('.rs-quick-country-btn').each(function () {
                var code = $(this).data('code');
                if (blockedCountries.has(code)) {
                    $(this).addClass('active');
                } else {
                    $(this).removeClass('active');
                }
            });
        }

        renderBlockedCountriesTags();

        // Quick Country Buttons (1-Click Toggle)
        $(document).on('click', '.rs-quick-country-btn', function (e) {
            e.preventDefault();
            var code = $(this).data('code');
            if (!code) return;

            if (blockedCountries.has(code)) {
                blockedCountries.delete(code);
                showNotice('Country ' + code + ' removed from blocklist.', false);
            } else {
                blockedCountries.add(code);
                $('#rocketslide-country-block-enabled').prop('checked', true);
                showNotice('Country ' + code + ' added to blocklist!', false);
            }
            renderBlockedCountriesTags();
        });

        // Remove Single Country Chip Click
        $(document).on('click', '.rs-remove-country-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var code = $(this).data('code');
            if (code && blockedCountries.has(code)) {
                blockedCountries.delete(code);
                renderBlockedCountriesTags();
                showNotice('Country ' + code + ' unblocked.', false);
            }
        });

        // Single Country Input: Add Country Button
        function addSingleCountry(saveImmediately) {
            var $input = $('#rs-single-country-input');
            var raw = ($input.val() || '').trim().toUpperCase();

            if (!raw || !/^[A-Z]{2}$/.test(raw)) {
                showNotice('Please enter a valid 2-letter ISO country code (e.g. PK, US, SA, DE).', true);
                $input.focus();
                return false;
            }

            blockedCountries.add(raw);
            $('#rocketslide-country-block-enabled').prop('checked', true);
            $input.val('');
            renderBlockedCountriesTags();

            if (saveImmediately) {
                showNotice('Country ' + raw + ' added! Saving now...', false);
                $('#rocketslide-save-fallback-btn').trigger('click');
            } else {
                showNotice('Country ' + raw + ' added to blocklist. Click Save to persist.', false);
            }
            return true;
        }

        $('#rs-add-single-country-btn').on('click', function (e) {
            e.preventDefault();
            addSingleCountry(false);
        });

        $('#rs-add-save-single-country-btn').on('click', function (e) {
            e.preventDefault();
            addSingleCountry(true);
        });

        $('#rs-single-country-input').on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addSingleCountry(false);
            }
        });

        // Clear All Blocked Countries Button Handlers
        $('#rs-clear-all-countries-btn').on('click', function (e) {
            e.preventDefault();
            if (blockedCountries.size === 0) {
                showNotice('No blocked countries to clear.', false);
                return;
            }
            blockedCountries.clear();
            renderBlockedCountriesTags();
            showNotice('All blocked countries cleared! Click Save to persist.', false);
        });

        $('#rs-clear-save-all-countries-btn').on('click', function (e) {
            e.preventDefault();
            blockedCountries.clear();
            renderBlockedCountriesTags();
            showNotice('All blocked countries cleared! Saving now...', false);
            $('#rocketslide-save-fallback-btn').trigger('click');
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

        // 6. Dual Upload Mode: Computer Local File OR Media Library
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

        // 7. Form Submission: Upload & Crop Image AJAX
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

        // 8. Save Individual Image Card Details
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

        // 9. Delete Image Card
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

        // 10. Load More Reels System: 4 Images on Mobile (<768px), 8 Images on Desktop (>=768px)
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
