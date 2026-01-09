<?php
/**
 * Класс для отладки отправки почты
 * 
 * @package LakendNotifier
 */

class Lakend_Notifier_Debug {
    
    private static $instance = null;
    
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action( 'admin_menu', array( $this, 'add_debug_page' ) );
        add_action( 'wp_ajax_lakend_test_smtp', array( $this, 'test_smtp_connection' ) );
    }
    
    public function add_debug_page() {
        add_submenu_page(
            'lakend-notifier',
            __( 'Email Debug', 'lakend-notifier' ),
            __( 'Email Debug', 'lakend-notifier' ),
            'manage_options',
            'lakend-notifier-debug',
            array( $this, 'render_debug_page' )
        );
    }
    
    public function render_debug_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Email Debug & Diagnostics', 'lakend-notifier' ); ?></h1>
            
            <div class="card" style="max-width: 800px; margin: 20px 0;">
                <h2><?php esc_html_e( 'System Information', 'lakend-notifier' ); ?></h2>
                <table class="widefat striped">
                    <tr>
                        <th><?php esc_html_e( 'WordPress Version', 'lakend-notifier' ); ?></th>
                        <td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'PHP Version', 'lakend-notifier' ); ?></th>
                        <td><?php echo esc_html( PHP_VERSION ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'SMTP Status', 'lakend-notifier' ); ?></th>
                        <td>
                            <?php 
                            if ( function_exists( 'ini_get' ) ) {
                                $smtp = ini_get( 'SMTP' );
                                $smtp_port = ini_get( 'smtp_port' );
                                echo $smtp ? esc_html( $smtp . ':' . $smtp_port ) : esc_html__( 'Not configured', 'lakend-notifier' );
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'PHP mail() function', 'lakend-notifier' ); ?></th>
                        <td><?php echo function_exists( 'mail' ) ? '✅ ' . esc_html__( 'Available', 'lakend-notifier' ) : '❌ ' . esc_html__( 'Not available', 'lakend-notifier' ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'WordPress wp_mail()', 'lakend-notifier' ); ?></th>
                        <td><?php echo function_exists( 'wp_mail' ) ? '✅ ' . esc_html__( 'Available', 'lakend-notifier' ) : '❌ ' . esc_html__( 'Not available', 'lakend-notifier' ); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="card" style="max-width: 800px; margin: 20px 0;">
                <h2><?php esc_html_e( 'Direct Email Test', 'lakend-notifier' ); ?></h2>
                <p><?php esc_html_e( 'Test email sending bypassing Lakend Notifier:', 'lakend-notifier' ); ?></p>
                
                <button type="button" class="button button-primary" id="lakend-direct-test">
                    <?php esc_html_e( 'Send Direct Test Email', 'lakend-notifier' ); ?>
                </button>
                
                <div id="lakend-direct-result" style="margin-top: 15px; display: none;"></div>
                
                <script>
                jQuery(document).ready(function($) {
                    $('#lakend-direct-test').on('click', function() {
                        var $button = $(this);
                        var $result = $('#lakend-direct-result');
                        
                        $button.prop('disabled', true);
                        $result.hide().empty();
                        
                        $.post('<?php echo admin_url( "admin-ajax.php" ); ?>', {
                            action: 'lakend_test_smtp',
                            nonce: '<?php echo wp_create_nonce( "lakend_debug_nonce" ); ?>'
                        }, function(response) {
                            var html = '';
                            if (response.success) {
                                html = '<div class="notice notice-success"><p>' + response.data.message + '</p>';
                                if (response.data.debug) {
                                    html += '<pre style="background: #f5f5f5; padding: 10px;">' + response.data.debug + '</pre>';
                                }
                                html += '</div>';
                            } else {
                                html = '<div class="notice notice-error"><p>' + response.data.message + '</p>';
                                if (response.data.debug) {
                                    html += '<pre style="background: #f5f5f5; padding: 10px;">' + response.data.debug + '</pre>';
                                }
                                html += '</div>';
                            }
                            $result.html(html).show();
                            $button.prop('disabled', false);
                        }).fail(function() {
                            $result.html('<div class="notice notice-error"><p>Connection error</p></div>').show();
                            $button.prop('disabled', false);
                        });
                    });
                });
                </script>
            </div>
            
            <div class="card" style="max-width: 800px; margin: 20px 0;">
                <h2><?php esc_html_e( 'Diagnostic Tools', 'lakend-notifier' ); ?></h2>
                
                <h3><?php esc_html_e( 'Check Server Logs', 'lakend-notifier' ); ?></h3>
                <p><?php esc_html_e( 'Recent errors from error_log:', 'lakend-notifier' ); ?></p>
                
                <button type="button" class="button" id="lakend-check-logs">
                    <?php esc_html_e( 'Check Error Logs', 'lakend-notifier' ); ?>
                </button>
                
                <div id="lakend-logs-result" style="margin-top: 15px; display: none;"></div>
                
                <script>
                jQuery(document).ready(function($) {
                    $('#lakend-check-logs').on('click', function() {
                        var $button = $(this);
                        var $result = $('#lakend-logs-result');
                        
                        $button.prop('disabled', true);
                        $result.hide().empty().html('<p>Loading...</p>').show();
                        
                        $.post('<?php echo admin_url( "admin-ajax.php" ); ?>', {
                            action: 'lakend_check_logs',
                            nonce: '<?php echo wp_create_nonce( "lakend_debug_nonce" ); ?>'
                        }, function(response) {
                            var html = '';
                            if (response.success) {
                                html = '<div class="notice notice-info"><p>Found ' + response.data.count + ' log entries:</p>';
                                html += '<pre style="background: #f5f5f5; padding: 10px; max-height: 300px; overflow: auto;">' + response.data.logs + '</pre>';
                                html += '</div>';
                            } else {
                                html = '<div class="notice notice-warning"><p>' + response.data.message + '</p></div>';
                            }
                            $result.html(html).show();
                            $button.prop('disabled', false);
                        }).fail(function() {
                            $result.html('<div class="notice notice-error"><p>Connection error</p></div>').show();
                            $button.prop('disabled', false);
                        });
                    });
                });
                </script>
            </div>
        </div>
        <?php
    }
    
    public function test_smtp_connection() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'lakend_debug_nonce' ) ) {
            wp_die( 'Security check failed' );
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Insufficient permissions' );
        }
        
        // Получаем настройки
        $options = get_option( 'lakend_notifier_settings', array() );
        $from_email = ! empty( $options['email_from_address'] ) ? 
            $options['email_from_address'] : 
            get_option( 'admin_email' );
        
        $recipients = array( get_option( 'admin_email' ) );
        
        // Подготавливаем тестовое письмо
        $subject = 'Lakend Notifier: Direct Email Test';
        $message = 'This is a direct email test from Lakend Notifier at ' . date('Y-m-d H:i:s');
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: Lakend Test <' . $from_email . '>'
        );
        
        // Отправляем напрямую через wp_mail
        $result = wp_mail( $recipients, $subject, $message, $headers );
        
        // Собираем отладочную информацию
        $debug = array();
        $debug[] = "Test Email Details:";
        $debug[] = "Recipient: " . $recipients[0];
        $debug[] = "From: " . $from_email;
        $debug[] = "Subject: " . $subject;
        $debug[] = "Result: " . ($result ? 'TRUE' : 'FALSE');
        
        // Проверяем PHPMailer если доступен
        global $phpmailer;
        if (isset($phpmailer)) {
            $debug[] = "\nPHPMailer Info:";
            $debug[] = "CharSet: " . $phpmailer->CharSet;
            $debug[] = "ContentType: " . $phpmailer->ContentType;
            $debug[] = "Encoding: " . $phpmailer->Encoding;
            
            if (is_wp_error($phpmailer->ErrorInfo)) {
                $debug[] = "Error: " . $phpmailer->ErrorInfo->get_error_message();
            }
        }
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Direct email test sent successfully!', 'lakend-notifier'),
                'debug' => implode("\n", $debug)
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Direct email test failed!', 'lakend-notifier'),
                'debug' => implode("\n", $debug)
            ));
        }
    }
}

// Инициализируем класс отладки
add_action('init', function() {
    if (is_admin()) {
        Lakend_Notifier_Debug::get_instance();
    }
});