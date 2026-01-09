<?php
/**
 * Тестовая страница плагина
 * 
 * @package LakendNotifier
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$options = get_option( 'lakend_notifier_settings', array() );
$sending_mode = isset( $options['sending_mode'] ) ? $options['sending_mode'] : 'both';
?>

<div class="wrap lakend-notifier-wrap">
    <div class="lakend-notifier-header">
        <h1>
            <span class="dashicons dashicons-testimonial"></span>
            <?php esc_html_e( 'Test Page', 'lakend-notifier' ); ?>
        </h1>
        <p><?php esc_html_e( 'Test the operation of the notification system in different modes', 'lakend-notifier' ); ?></p>
        
        <div style="margin-top: 20px;">
            <a href="<?php echo admin_url( 'admin.php?page=lakend-notifier-debug' ); ?>" class="button button-secondary">
                <span class="dashicons dashicons-search"></span>
                <?php esc_html_e( 'Email Debug & Diagnostics', 'lakend-notifier' ); ?>
            </a>
        </div>
    </div>
    
    <div class="lakend-card">
        <h2><?php esc_html_e( 'Current Settings', 'lakend-notifier' ); ?></h2>
        
        <table class="lakend-settings-table">
            <tr>
                <th><?php esc_html_e( 'Sending Mode', 'lakend-notifier' ); ?>:</th>
                <td>
                    <span class="lakend-badge <?php echo $sending_mode === 'both' ? 'badge-success' : 'badge-info'; ?>">
                        <?php 
                        echo $sending_mode === 'both' ? 
                            esc_html__( 'Send to both email and Telegram', 'lakend-notifier' ) : 
                            esc_html__( 'Send to email, notify in Telegram', 'lakend-notifier' );
                        ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Email Sender', 'lakend-notifier' ); ?>:</th>
                <td>
                    <?php echo esc_html( isset( $options['email_from_name'] ) ? $options['email_from_name'] : get_bloginfo( 'name' ) ); ?>
                    &lt;<?php echo esc_html( isset( $options['email_from_address'] ) ? $options['email_from_address'] : get_option( 'admin_email' ) ); ?>&gt;
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Telegram Status', 'lakend-notifier' ); ?>:</th>
                <td>
                    <?php if ( ! empty( $options['telegram_bot_token'] ) && ! empty( $options['telegram_chat_id'] ) ) : ?>
                        <span class="lakend-badge badge-success">
                            <?php esc_html_e( 'Configured', 'lakend-notifier' ); ?>
                        </span>
                    <?php else : ?>
                        <span class="lakend-badge badge-error">
                            <?php esc_html_e( 'Not configured', 'lakend-notifier' ); ?>
                        </span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        
        <p style="margin-top: 15px;">
            <a href="<?php echo admin_url( 'admin.php?page=lakend-notifier' ); ?>" class="button">
                <?php esc_html_e( 'Edit Settings', 'lakend-notifier' ); ?>
            </a>
        </p>
    </div>
    
    <div class="lakend-card">
        <h2><?php esc_html_e( 'Quick Test', 'lakend-notifier' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Send a test notification with current settings', 'lakend-notifier' ); ?></p>
        
        <div class="lakend-test-buttons" style="margin: 20px 0;">
            <button type="button" class="button button-primary lakend-send-test" data-mode="both">
                <?php esc_html_e( 'Test Mode: Both channels', 'lakend-notifier' ); ?>
            </button>
            <button type="button" class="button button-primary lakend-send-test" data-mode="email_only_notify">
                <?php esc_html_e( 'Test Mode: Email + Telegram notification', 'lakend-notifier' ); ?>
            </button>
        </div>
        
        <div id="lakend-test-result" style="margin-top: 20px; display: none;"></div>
    </div>
    
    <div class="lakend-card">
        <h2><?php esc_html_e( 'Advanced Testing', 'lakend-notifier' ); ?></h2>
        
        <form id="lakend-advanced-test">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="test-mode"><?php esc_html_e( 'Test Mode', 'lakend-notifier' ); ?></label>
                    </th>
                    <td>
                        <select id="test-mode" name="test_mode">
                            <option value="both"><?php esc_html_e( 'Send to both email and Telegram', 'lakend-notifier' ); ?></option>
                            <option value="email_only_notify"><?php esc_html_e( 'Send to email, notify in Telegram', 'lakend-notifier' ); ?></option>
                            <option value="email_only"><?php esc_html_e( 'Email only', 'lakend-notifier' ); ?></option>
                            <option value="telegram_only"><?php esc_html_e( 'Telegram only', 'lakend-notifier' ); ?></option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="test-subject"><?php esc_html_e( 'Subject', 'lakend-notifier' ); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="test-subject" 
                               name="test_subject" 
                               class="regular-text" 
                               value="<?php esc_attr_e( 'Test notification from Lakend Notifier', 'lakend-notifier' ); ?>">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="test-message"><?php esc_html_e( 'Message', 'lakend-notifier' ); ?></label>
                    </th>
                    <td>
                        <textarea id="test-message" 
                                  name="test_message" 
                                  rows="5" 
                                  class="large-text"><?php 
                            esc_html_e( 'This is a test message sent to verify the functionality of the Lakend Notifier plugin.

Parameters:
- Date: ' . date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) . '
- Site: ' . get_bloginfo( 'name' ) . '
- URL: ' . home_url() . '

This message allows you to check:
✓ Email formatting
✓ Telegram notification delivery
✓ System operation in different modes', 'lakend-notifier' ); 
                        ?></textarea>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="test-recipients"><?php esc_html_e( 'Email Recipients', 'lakend-notifier' ); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="test-recipients" 
                               name="test_recipients" 
                               class="regular-text" 
                               value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>"
                               placeholder="<?php esc_attr_e( 'Comma separated emails', 'lakend-notifier' ); ?>">
                        <p class="description"><?php esc_html_e( 'If empty, the default recipient from settings will be used', 'lakend-notifier' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="test-type"><?php esc_html_e( 'Notification Type', 'lakend-notifier' ); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="test-type" 
                               name="test_type" 
                               class="regular-text" 
                               value="test_notification"
                               placeholder="test_notification">
                        <p class="description"><?php esc_html_e( 'Used for logging and channel configuration', 'lakend-notifier' ); ?></p>
                    </td>
                </tr>
            </table>
            
            <p>
                <button type="submit" class="button button-primary">
                    <?php esc_html_e( 'Run Advanced Test', 'lakend-notifier' ); ?>
                </button>
                <button type="button" class="button" id="lakend-reset-form">
                    <?php esc_html_e( 'Reset to Default', 'lakend-notifier' ); ?>
                </button>
            </p>
        </form>
        
        <div id="lakend-advanced-result" style="margin-top: 20px; display: none;"></div>
    </div>
    
    <div class="lakend-card">
        <h2><?php esc_html_e( 'Test Results History', 'lakend-notifier' ); ?></h2>
        
        <div id="lakend-test-history">
            <div class="lakend-loading"><?php esc_html_e( 'Loading...', 'lakend-notifier' ); ?></div>
        </div>
        
        <p style="text-align: center; margin-top: 15px;">
            <button type="button" class="button" id="lakend-refresh-history">
                <?php esc_html_e( 'Refresh History', 'lakend-notifier' ); ?>
            </button>
        </p>
    </div>
    <div class="lakend-card">
        <h2><?php esc_html_e( 'Test Results with Details', 'lakend-notifier' ); ?></h2>
        
        <div style="margin: 20px 0;">
            <button type="button" class="button button-primary" id="lakend-test-with-details">
                <?php esc_html_e( 'Send Test with Detailed Results', 'lakend-notifier' ); ?>
            </button>
        </div>
        
        <div id="lakend-detailed-results" style="margin-top: 20px; display: none;"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#lakend-test-with-details').on('click', function() {
        var $button = $(this);
        var $results = $('#lakend-detailed-results');
        
        $button.prop('disabled', true);
        $results.hide().empty();
        
        $.post('<?php echo admin_url( "admin-ajax.php" ); ?>', {
            action: 'lakend_send_test',
            type: 'test_both',
            detailed: '1',
            nonce: '<?php echo wp_create_nonce( "lakend_test_nonce" ); ?>'
        }, function(response) {
            var html = '';
            
            if (response.success) {
                html += '<div class="notice notice-success">';
                html += '<p><strong>✅ ' + response.data.message + '</strong></p>';
                
                if (response.data.detailed) {
                    html += '<h4>Detailed Results:</h4>';
                    html += '<pre style="background: #f5f5f5; padding: 10px; max-height: 400px; overflow: auto;">';
                    
                    var details = response.data.detailed;
                    for (var channel in details) {
                        if (details.hasOwnProperty(channel)) {
                            var channelResult = details[channel];
                            html += '\n=== ' + channel.toUpperCase() + ' ===\n';
                            html += 'Status: ' + (channelResult.success ? '✅ SUCCESS' : '❌ FAILED') + '\n';
                            html += 'Message: ' + channelResult.message + '\n';
                            
                            if (channelResult.debug) {
                                html += 'Debug: ' + channelResult.debug + '\n';
                            }
                        }
                    }
                    
                    html += '</pre>';
                }
                
                html += '</div>';
            } else {
                html = '<div class="notice notice-error"><p><strong>❌ ' + response.data.message + '</strong></p>';
                
                if (response.data.debug) {
                    html += '<pre style="background: #f5f5f5; padding: 10px;">' + response.data.debug + '</pre>';
                }
                
                html += '</div>';
            }
            
            $results.html(html).show();
            $button.prop('disabled', false);
            
        }).fail(function(xhr, status, error) {
            $results.html(
                '<div class="notice notice-error"><p>AJAX Error: ' + error + '</p><p>Status: ' + xhr.status + '</p></div>'
            ).show();
            $button.prop('disabled', false);
        });
    });
});
</script>