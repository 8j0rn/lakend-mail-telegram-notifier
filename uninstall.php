<?php
/**
 * Очистка при удалении плагина
 * 
 * @package LakendNotifier
 */

// Если скрипт вызывается напрямую, прекращаем выполнение
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Функция удаления плагина
 */
function lakend_notifier_uninstall() {
    global $wpdb;
    
    // Получаем настройки плагина
    $delete_data = get_option( 'lakend_notifier_delete_data', false );
    
    // Если пользователь не разрешил удаление данных, выходим
    if ( ! $delete_data ) {
        return;
    }
    
    // Удаляем таблицу логов
    $table_name = $wpdb->prefix . 'lakend_notifier_logs';
    $wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
    
    // Удаляем опции
    $options = array(
        'lakend_notifier_settings',
        'lakend_notifier_version',
        'lakend_notifier_default_settings_set',
        'lakend_notifier_delete_data',
    );
    
    foreach ( $options as $option ) {
        delete_option( $option );
        delete_site_option( $option ); // Для мультисайтов
    }
    
    // Удаляем крон задачи
    wp_clear_scheduled_hook( 'lakend_notifier_cleanup_logs' );
    
    // Удаляем кэш если используется
    if ( function_exists( 'wp_cache_flush' ) ) {
        wp_cache_flush();
    }
}

// Вызываем функцию удаления
lakend_notifier_uninstall();