/**
 * Valley Virtual Assistant - Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Initialize select2 for enhanced selects if available
        if (typeof $.fn.select2 !== 'undefined') {
            $('select.enhanced-select').select2({
                minimumResultsForSearch: 10,
                width: '100%'
            });
        }

        // Test API Connection
        $('#vva-test-api, #vva-test-api-connection').on('click', function(e) {
            e.preventDefault();

            const $button = $(this);
            const $result = $('#vva-test-result');
            const originalText = $button.text();

            $button.prop('disabled', true).text('Testing...');
            $result.removeClass('success error').html('<span class="vva-loading"></span>');

            $.ajax({
                url: vvaAdminData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vva_test_api',
                    nonce: vvaAdminData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $result.addClass('success').text('✓ ' + response.data.message);
                    } else {
                        $result.addClass('error').text('✗ Error: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    $result.addClass('error').text('✗ Connection failed: ' + error);
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        });

        // Color Picker Enhancement
        if (typeof $.fn.wpColorPicker !== 'undefined') {
            $('.color-field').wpColorPicker();
        }

        // Settings Form Auto-save Indicator
        $('.wrap form').on('submit', function() {
            const $submit = $(this).find('[type="submit"]');
            $submit.prop('disabled', true);

            setTimeout(function() {
                $submit.prop('disabled', false);
            }, 2000);
        });

        // Analytics Auto-refresh (optional)
        if ($('.vva-stats-grid').length) {
            // Refresh analytics every 60 seconds
            setInterval(function() {
                loadAnalytics();
            }, 60000);
        }

        function loadAnalytics() {
            const period = new URLSearchParams(window.location.search).get('period') || '7days';

            $.ajax({
                url: vvaAdminData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vva_get_analytics',
                    nonce: vvaAdminData.nonce,
                    period: period
                },
                success: function(response) {
                    if (response.success) {
                        updateAnalyticsDisplay(response.data);
                    }
                }
            });
        }

        function updateAnalyticsDisplay(data) {
            // Update stats cards with new data
            if (data.total_conversations !== undefined) {
                $('.vva-stats-grid .vva-stat-card:eq(0) h3').text(
                    number_format(data.total_conversations)
                );
            }
            if (data.total_messages !== undefined) {
                $('.vva-stats-grid .vva-stat-card:eq(1) h3').text(
                    number_format(data.total_messages)
                );
            }
            if (data.avg_messages_per_conversation !== undefined) {
                $('.vva-stats-grid .vva-stat-card:eq(2) h3').text(
                    data.avg_messages_per_conversation
                );
            }
        }

        function number_format(number) {
            return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        // Conversation Search/Filter (if implemented)
        $('#vva-conversation-search').on('keyup', debounce(function() {
            const searchTerm = $(this).val().toLowerCase();

            $('.wp-list-table tbody tr').each(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(searchTerm) > -1);
            });
        }, 300));

        function debounce(func, wait) {
            let timeout;
            return function() {
                const context = this;
                const args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    func.apply(context, args);
                }, wait);
            };
        }

        // Confirm Delete Actions
        $('.vva-delete-conversation').on('click', function(e) {
            if (!confirm('Are you sure you want to delete this conversation? This action cannot be undone.')) {
                e.preventDefault();
            }
        });

        // Tab Navigation Memory
        if (window.location.hash) {
            $('.nav-tab-wrapper a[href="' + window.location.hash + '"]').click();
        }

        $('.nav-tab-wrapper a').on('click', function() {
            const hash = $(this).attr('href').split('&tab=')[1];
            if (hash) {
                window.location.hash = 'tab=' + hash;
            }
        });

    });

})(jQuery);
