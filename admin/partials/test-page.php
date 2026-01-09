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

<div class="wrap">
    <h1><?php esc_html_e( 'Test Page', 'lakend-notifier' ); ?></h1>
    <p><?php esc_html_e( 'Test the notification system', 'lakend-notifier' ); ?></p>
    
    <div class="card" style="max-width: 600px; margin: 20px 0;">
        <h2><?php esc_html_e( 'Quick Tests', 'lakend-notifier' ); ?></h2>
        
        <div style="margin: 20px 0;">
            <button type="button" class="button button-primary" id="lakend-test-email">
                <?php esc_html_e( 'Test Email', 'lakend-notifier' ); ?>
            </button>
            <button type="button" class="button button-primary" id="lakend-test-telegram">
                <?php esc_html_e( 'Test Telegram', 'lakend-notifier' ); ?>
            </button>
            <button type="button" class="button button-primary" id="lakend-test-both">
                <?php esc_html_e( 'Test Both', 'lakend-notifier' ); ?>
            </button>
        </div>
        
        <div id="lakend-test-result" style="display: none; margin-top: 15px;"></div>
    </div>
    
    <div class="card" style="max-width: 600px; margin: 20px 0;">
        <h2><?php esc_html_e( 'Current Settings', 'lakend-notifier' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th><?php esc_html_e( 'Sending Mode', 'lakend-notifier' ); ?>:</th>
                <td>
                    <?php if ( $sending_mode === 'both' ) : ?>
                        <span style="color: green; font-weight: bold;">
                            <?php esc_html_e( 'Send to both email and Telegram', 'lakend-notifier' ); ?>
                        </span>
                    <?php else : ?>
                        <span style="color: blue; font-weight: bold;">
                            <?php esc_html_e( 'Send to email, notify in Telegram', 'lakend-notifier' ); ?>
                        </span>
                    <?php endif; ?>
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
                        <span style="color: green; font-weight: bold;">✅ <?php esc_html_e( 'Configured', 'lakend-notifier' ); ?></span>
                    <?php else : ?>
                        <span style="color: red; font-weight: bold;">❌ <?php esc_html_e( 'Not configured', 'lakend-notifier' ); ?></span>
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
</div>

<script>
jQuery(document).ready(function($) {
    function sendTest(type) {
        $('#lakend-test-result').hide().empty();
        
        $.post('<?php echo admin_url( "admin-ajax.php" ); ?>', {
            action: 'lakend_send_test',
            type: type,
            nonce: '<?php echo wp_create_nonce( "lakend_test_nonce" ); ?>'
        }, function(response) {
            if (response.success) {
                $('#lakend-test-result').html(
                    '<div class="notice notice-success"><p>' + response.data.message + '</p></div>'
                ).show();
            } else {
                $('#lakend-test-result').html(
                    '<div class="notice notice-error"><p>' + response.data.message + '</p></div>'
                ).show();
            }
        }).fail(function() {
            $('#lakend-test-result').html(
                '<div class="notice notice-error"><p>Connection error</p></div>'
            ).show();
        });
    }
    
    $('#lakend-test-email').on('click', function() { 
        sendTest('test_email'); 
    });
    
    $('#lakend-test-telegram').on('click', function() { 
        sendTest('test_telegram'); 
    });
    
    $('#lakend-test-both').on('click', function() { 
        sendTest('test_both'); 
    });
});
</script>