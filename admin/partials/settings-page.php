<?php
/**
 * Страница настроек плагина
 * 
 * @package LakendNotifier
 */

// Защита от прямого доступа
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap lakend-notifier-wrap">
    <div class="lakend-notifier-header">
        <h1>
            <span class="dashicons dashicons-megaphone"></span>
            <?php esc_html_e( 'Lakend Mail-Telegram Notifier', 'lakend-notifier' ); ?>
        </h1>
        <p><?php esc_html_e( 'Centralized notification system for your site', 'lakend-notifier' ); ?></p>
    </div>
    
    <?php if ( isset( $_GET['settings-updated'] ) ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Settings saved!', 'lakend-notifier' ); ?></p>
        </div>
    <?php endif; ?>
    
    <form method="post" action="options.php">
        <?php
        settings_fields( 'lakend_notifier_settings' );
        do_settings_sections( 'lakend-notifier' );
        submit_button();
        ?>
    </form>
    
    <div class="lakend-card" style="margin-top: 40px;">
        <h2><?php esc_html_e( 'Quick Test', 'lakend-notifier' ); ?></h2>
        <p><?php esc_html_e( 'Test your settings with a quick notification:', 'lakend-notifier' ); ?></p>
        
        <div class="lakend-test-buttons">
            <button type="button" class="button button-primary lakend-test-send" data-channel="email">
                <?php esc_html_e( 'Test Email', 'lakend-notifier' ); ?>
            </button>
            <button type="button" class="button button-primary lakend-test-send" data-channel="telegram">
                <?php esc_html_e( 'Test Telegram', 'lakend-notifier' ); ?>
            </button>
            <button type="button" class="button button-primary lakend-test-send" data-channel="both">
                <?php esc_html_e( 'Test Both', 'lakend-notifier' ); ?>
            </button>
        </div>
        
        <div class="lakend-test-result" id="lakend-settings-test-result" style="display: none;"></div>
    </div>
</div>