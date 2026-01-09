/**
 * Lakend Notifier - Админ скрипты
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Тестирование отправки на странице настроек
        $('.lakend-test-send').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var channel = $button.data('channel');
            var $result = $('#lakend-settings-test-result');
            
            $button.prop('disabled', true);
            $result.hide().empty();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'lakend_send_test',
                    type: 'test_' + channel,
                    nonce: lakend_notifier.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $result.html(
                            '<div class="notice notice-success"><p>' + response.data.message + '</p></div>'
                        ).show();
                    } else {
                        $result.html(
                            '<div class="notice notice-error"><p>' + response.data.message + '</p></div>'
                        ).show();
                    }
                },
                error: function() {
                    $result.html(
                        '<div class="notice notice-error"><p>Connection error</p></div>'
                    ).show();
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });
    });
    
})(jQuery);