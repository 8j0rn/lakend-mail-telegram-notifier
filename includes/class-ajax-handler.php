<?php
/**
 * Обработчик AJAX запросов
 * 
 * @package LakendNotifier
 */

class Lakend_Notifier_Ajax_Handler {
    
    private static $instance = null;
    
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->setup_hooks();
    }
    
    private function setup_hooks() {
        add_action( 'wp_ajax_lakend_send_test', array( $this, 'send_test' ) );
        add_action( 'wp_ajax_lakend_test_telegram_connection', array( $this, 'test_telegram_connection' ) );
        add_action( 'wp_ajax_lakend_test_smtp', array( $this, 'test_smtp_connection' ) );
        add_action( 'wp_ajax_lakend_check_logs', array( $this, 'check_error_logs' ) );
    }
    
    /**
     * Отправка тестового уведомления
     */
    public function send_test() {
        // Проверяем nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'lakend_test_nonce' ) ) {
            wp_die( 'Security check failed' );
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Insufficient permissions' );
        }
        
        $type = sanitize_text_field( $_POST['type'] );
        $detailed = isset( $_POST['detailed'] ) && $_POST['detailed'] == '1';
        
        $subject = __( 'Test notification', 'lakend-notifier' );
        $message = __( 'This is a test message sent on', 'lakend-notifier' ) . ' ' . date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
        
        // Определяем тип теста
        $test_type = 'test';
        if ( $type === 'test_email' ) {
            $test_type = 'email_only';
        } elseif ( $type === 'test_telegram' ) {
            $test_type = 'telegram_only';
        } elseif ( $type === 'test_both' ) {
            $test_type = 'both_channels';
        }
        
        try {
            $result = lakend_send_notification( $subject, $message, $test_type );
            
            if ( ! empty( $result ) ) {
                // Проверяем, была ли успешная отправка
                $success = false;
                $detailed_results = array();
                
                foreach ( $result as $channel => $channel_result ) {
                    if ( ! empty( $channel_result['success'] ) ) {
                        $success = true;
                    }
                    
                    // Собираем детальную информацию
                    if ($detailed) {
                        $detailed_results[$channel] = $channel_result;
                        
                        // Добавляем отладочную информацию для email
                        if ($channel === 'email') {
                            global $phpmailer;
                            if (isset($phpmailer)) {
                                $detailed_results[$channel]['phpmailer_debug'] = array();
                                
                                // Получаем больше информации из PHPMailer
                                $detailed_results[$channel]['phpmailer_debug']['From'] = $phpmailer->From;
                                $detailed_results[$channel]['phpmailer_debug']['FromName'] = $phpmailer->FromName;
                                $detailed_results[$channel]['phpmailer_debug']['CharSet'] = $phpmailer->CharSet;
                                $detailed_results[$channel]['phpmailer_debug']['ContentType'] = $phpmailer->ContentType;
                                $detailed_results[$channel]['phpmailer_debug']['Encoding'] = $phpmailer->Encoding;
                                $detailed_results[$channel]['phpmailer_debug']['Mailer'] = $phpmailer->Mailer;
                                $detailed_results[$channel]['phpmailer_debug']['SMTPDebug'] = $phpmailer->SMTPDebug;
                                $detailed_results[$channel]['phpmailer_debug']['ErrorInfo'] = $phpmailer->ErrorInfo;
                                
                                // Если есть ошибка
                                if (is_wp_error($phpmailer->ErrorInfo)) {
                                    $detailed_results[$channel]['phpmailer_debug']['Error'] = $phpmailer->ErrorInfo->get_error_message();
                                }
                                
                                // Добавляем информацию о получателях
                                $detailed_results[$channel]['phpmailer_debug']['Recipients'] = array();
                                if (!empty($phpmailer->getToAddresses())) {
                                    $detailed_results[$channel]['phpmailer_debug']['Recipients'] = $phpmailer->getToAddresses();
                                }
                            }
                        }
                    }
                }
                
                if ( $success ) {
                    $response = array(
                        'message' => __( 'Test notification sent successfully', 'lakend-notifier' )
                    );
                    
                    if ($detailed) {
                        $response['detailed'] = $detailed_results;
                    }
                    
                    wp_send_json_success( $response );
                } else {
                    $response = array(
                        'message' => __( 'All sending attempts failed', 'lakend-notifier' )
                    );
                    
                    if ($detailed) {
                        $response['detailed'] = $detailed_results;
                    }
                    
                    wp_send_json_error( $response );
                }
            } else {
                wp_send_json_error( array(
                    'message' => __( 'No results from sending', 'lakend-notifier' )
                ) );
            }
        } catch ( Exception $e ) {
            wp_send_json_error( array(
                'message' => $e->getMessage(),
                'debug' => 'Exception: ' . $e->getFile() . ':' . $e->getLine()
            ) );
        }
    }
    
    /**
     * Проверка error_log на наличие записей плагина
     */
    public function check_error_logs() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'lakend_debug_nonce' ) ) {
            wp_die( 'Security check failed' );
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Insufficient permissions' );
        }
        
        // Пытаемся прочитать error_log
        $log_file = ini_get('error_log');
        $logs = array();
        $count = 0;
        
        if ($log_file && file_exists($log_file)) {
            // Читаем последние 50 строк
            $lines = file($log_file, FILE_IGNORE_NEW_LINES);
            $lines = array_slice($lines, -50); // Последние 50 строк
            
            foreach ($lines as $line) {
                if (strpos($line, 'Lakend') !== false) {
                    $logs[] = $line;
                    $count++;
                }
            }
        }
        
        if ($count > 0) {
            wp_send_json_success(array(
                'count' => $count,
                'logs' => implode("\n", $logs)
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'No Lakend-related logs found in error_log'
            ));
        }
    }
}