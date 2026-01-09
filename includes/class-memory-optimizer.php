<?php
/**
 * Оптимизатор памяти для плагина
 * 
 * @package LakendNotifier
 */

class Lakend_Notifier_Memory_Optimizer {
    
    /**
     * Инициализация
     */
    public static function init() {
        add_action( 'lakend_notifier_before_send', array( __CLASS__, 'increase_memory_limit' ) );
        add_action( 'lakend_notifier_after_send', array( __CLASS__, 'cleanup_memory' ) );
    }
    
    // Исправленный код для class-memory-optimizer.php
    /**
     * Увеличение лимита памяти
     */
    public static function increase_memory_limit() {
        // Простая проверка и увеличение лимита
        $current_limit = @ini_get( 'memory_limit' );
        $current_limit_int = self::convert_to_bytes( $current_limit );
        
        // Если лимит меньше 256M, увеличиваем
        if ( $current_limit_int < 256 * 1024 * 1024 ) {
            @ini_set( 'memory_limit', '256M' );
        }
        
        // Добавляем фильтр для WordPress
        add_filter( 'wp_memory_limit', function( $limit ) {
            $limit_int = self::convert_to_bytes( $limit );
            if ( $limit_int < 256 * 1024 * 1024 ) {
                return '256M';
            }
            return $limit;
        } );
    }
    
    /**
     * Очистка памяти
     */
    public static function cleanup_memory() {
        // Принудительный вызов сборщика мусора
        if ( function_exists( 'gc_collect_cycles' ) ) {
            gc_collect_cycles();
        }
    }
    
    /**
     * Конвертация размера памяти в байты
     */
    private static function convert_to_bytes( $size ) {
        $size = trim( $size );
        $last = strtolower( $size[ strlen( $size ) - 1 ] );
        $size = intval( $size );
        
        switch ( $last ) {
            case 'g':
                $size *= 1024;
            case 'm':
                $size *= 1024;
            case 'k':
                $size *= 1024;
        }
        
        return $size;
    }
}