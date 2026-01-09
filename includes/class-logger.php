<?php
/**
 * Логгер уведомлений
 * 
 * @package LakendNotifier
 */

class Lakend_Notifier_Logger {
    
    /**
     * Логирование уведомления
     */
    public function log( $subject, $type, $results, $data ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lakend_notifier_logs';
        
        // Проверяем существование таблицы
        if ( ! $this->table_exists( $table_name ) ) {
            return false;
        }
        
        // Определяем общий успех отправки
        $success = 0;
        if ( is_array( $results ) ) {
            foreach ( $results as $channel_result ) {
                if ( ! empty( $channel_result['success'] ) ) {
                    $success = 1;
                    break;
                }
            }
        }
        
        $insert_data = array(
            'subject'   => substr( $subject, 0, 255 ),
            'type'      => $type,
            'channels'  => is_array( $results ) ? wp_json_encode( $results ) : '[]',
            'success'   => $success,
            'data'      => is_array( $data ) ? wp_json_encode( $data ) : '[]',
            'created_at' => current_time( 'mysql' ),
        );
        
        $insert_format = array( '%s', '%s', '%s', '%d', '%s', '%s' );
        
        return $wpdb->insert( $table_name, $insert_data, $insert_format );
    }
    
    /**
     * Получить последние логи
     */
    public function get_recent_logs( $limit = 50, $type = '' ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lakend_notifier_logs';
        
        // Проверяем существование таблицы
        if ( ! $this->table_exists( $table_name ) ) {
            return array();
        }
        
        $sql = "SELECT * FROM {$table_name}";
        $params = array();
        
        if ( $type ) {
            $sql .= " WHERE type = %s";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT %d";
        $params[] = intval( $limit );
        
        // Используем prepare только если есть параметры
        if ( ! empty( $params ) ) {
            $sql = $wpdb->prepare( $sql, $params );
        }
        
        $results = $wpdb->get_results( $sql );
        
        return is_array( $results ) ? $results : array();
    }
    
    /**
     * Получить логи по дате
     */
    public function get_logs_by_date( $date ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lakend_notifier_logs';
        
        // Проверяем существование таблицы
        if ( ! $this->table_exists( $table_name ) ) {
            return array();
        }
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$table_name} 
            WHERE DATE(created_at) = %s 
            ORDER BY created_at DESC",
            $date
        );
        
        $results = $wpdb->get_results( $sql );
        
        return is_array( $results ) ? $results : array();
    }
    
    /**
     * Проверка существования таблицы
     */
    private function table_exists( $table_name ) {
        global $wpdb;
        
        // Кэшируем результат проверки
        static $tables_cache = array();
        
        if ( isset( $tables_cache[ $table_name ] ) ) {
            return $tables_cache[ $table_name ];
        }
        
        // Исправляем: используем правильный синтаксис для prepare
        $sql = $wpdb->prepare( 
            "SHOW TABLES LIKE %s",
            $table_name 
        );
        
        $result = $wpdb->get_var( $sql );
        
        $tables_cache[ $table_name ] = ( $result === $table_name );
        
        return $tables_cache[ $table_name ];
    }
}