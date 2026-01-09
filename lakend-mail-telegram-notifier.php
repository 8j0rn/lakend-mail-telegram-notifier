<?php
/**
 * Plugin Name:  Lakend Mail-Telegram Notifier
 * Plugin URI:   https://lakend.ru/
 * Description:  Централизованная система уведомлений с поддержкой Email, Telegram и других каналов. Заменяет стандартные методы отправки.
 * Version:      1.2.1
 * Author:       8j0rn
 * Author URI:   https://lakend.ru/
 * License:      GPL v2 or later
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  lakend-notifier
 * Domain Path:  /languages
 * Requires PHP: 7.4
 * 
 * @package LakendNotifier
 */

// Если кто-то пытается напрямую обратиться к файлу - выходим
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Константы плагина
 */
define( 'LAKEND_NOTIFIER_VERSION', '1.0.0' );
define( 'LAKEND_NOTIFIER_FILE', __FILE__ );
define( 'LAKEND_NOTIFIER_PATH', plugin_dir_path( __FILE__ ) );
define( 'LAKEND_NOTIFIER_URL', plugin_dir_url( __FILE__ ) );
define( 'LAKEND_NOTIFIER_BASENAME', plugin_basename( __FILE__ ) );

// Подключаем вспомогательные функции для шаблонов
require_once LAKEND_NOTIFIER_PATH . 'includes/functions.php';

/**
 * Проверка совместимости при активации
 */
register_activation_hook( __FILE__, 'lakend_notifier_activation_check' );
function lakend_notifier_activation_check() {
    $php_version = '7.4';
    $wp_version  = '5.6';
    
    if ( version_compare( PHP_VERSION, $php_version, '<' ) ) {
        deactivate_plugins( basename( __FILE__ ) );
        wp_die(
            sprintf(
                /* translators: %s: PHP version */
                esc_html__( 'Для работы плагина Lakend Notifier требуется PHP версии %s или выше.', 'lakend-notifier' ),
                $php_version
            ),
            esc_html__( 'Ошибка активации', 'lakend-notifier' ),
            array( 'back_link' => true )
        );
    }
    
    if ( version_compare( $GLOBALS['wp_version'], $wp_version, '<' ) ) {
        deactivate_plugins( basename( __FILE__ ) );
        wp_die(
            sprintf(
                /* translators: %s: WordPress version */
                esc_html__( 'Для работы плагина Lakend Notifier требуется WordPress версии %s или выше.', 'lakend-notifier' ),
                $wp_version
            ),
            esc_html__( 'Ошибка активации', 'lakend-notifier' ),
            array( 'back_link' => true )
        );
    }
    
    // Создаем таблицы в БД если нужно
    require_once LAKEND_NOTIFIER_PATH . 'includes/class-core.php';
    Lakend_Notifier_Core::create_tables();
    
    // Добавляем дефолтные настройки
    add_option( 'lakend_notifier_default_settings_set', true );
}

/**
 * Автозагрузчик классов
 */
spl_autoload_register( function( $class_name ) {
    // Проверяем, относится ли класс к нашему плагину
    if ( strpos( $class_name, 'Lakend_Notifier_' ) === 0 ) {
        $file_name = 'class-' . strtolower( str_replace( '_', '-', substr( $class_name, 16 ) ) ) . '.php';
        $file_path = LAKEND_NOTIFIER_PATH . 'includes/' . $file_name;
        
        // Проверяем существование файла
        if ( file_exists( $file_path ) ) {
            // Используем require_once вместо require
            require_once $file_path;
        }
    }
} );

/**
 * Инициализация плагина - загружаем на init для совместимости с WordPress 6.7+
 */
add_action( 'init', 'lakend_notifier_init' );
function lakend_notifier_init() {
    // Загружаем текстовый домен для локализации
    load_plugin_textdomain(
        'lakend-notifier',
        false,
        dirname( LAKEND_NOTIFIER_BASENAME ) . '/languages'
    );
    
    // Инициализируем ядро
    Lakend_Notifier_Core::get_instance();
    
    // Инициализируем админку если нужно
    if ( is_admin() ) {
        Lakend_Notifier_Admin::get_instance();
    }
    
    // Инициализируем AJAX обработчик
    Lakend_Notifier_Ajax_Handler::get_instance();
}

/**
 * Глобальная функция-помощник для отправки уведомлений
 * 
 * @param string $subject Тема уведомления
 * @param string $message Тело сообщения
 * @param string $type Тип уведомления (определяет настройки каналов)
 * @param array  $data Дополнительные данные
 * @return array Результаты отправки по каналам
 */
function lakend_send_notification( $subject, $message, $type = 'default', $data = array() ) {
    return Lakend_Notifier_Core::get_instance()->send( $subject, $message, $type, $data );
}

/**
 * Функция для обратной совместимости со старым кодом
 * Заменяет стандартный wp_mail на нашу систему
 */
add_filter( 'pre_wp_mail', 'lakend_intercept_wp_mail', 999, 2 );
// В основном файле плагина (lakend-mail-telegram-notifier.php)
function lakend_intercept_wp_mail( $return_null, $mail_data ) {
    static $in_progress = false; // Флаг для текущей обработки
    static $processed_hashes = array(); // Хеши уже обработанных сообщений
    static $hash_cache_size = 50; // Максимальное количество хранимых хешей
    
    // Защита от рекурсии - если уже в процессе, выходим
    if ( $in_progress ) {
        return $return_null;
    }
    
    $options = get_option( 'lakend_notifier_settings', array() );
    
    if ( ! empty( $options['intercept_all_wp_mail'] ) && $options['intercept_all_wp_mail'] ) {
        $in_progress = true; // Устанавливаем флаг
        
        try {
            // Генерируем хеш сообщения
            $message_hash = md5( 
                $mail_data['subject'] . 
                serialize( $mail_data['to'] ) . 
                substr( is_string( $mail_data['message'] ) ? $mail_data['message'] : serialize( $mail_data['message'] ), 0, 500 )
            );
            
            // Проверяем, не обрабатывали ли мы это сообщение недавно
            if ( isset( $processed_hashes[ $message_hash ] ) ) {
                // Если прошло меньше 5 секунд - скорее всего это рекурсия
                if ( time() - $processed_hashes[ $message_hash ] < 5 ) {
                    error_log( 'Lakend Notifier: возможная рекурсия, пропускаем сообщение: ' . $mail_data['subject'] );
                    return true; // Блокируем стандартную отправку, но сами не отправляем
                }
            }
            
            // Сохраняем хеш с меткой времени
            $processed_hashes[ $message_hash ] = time();
            
            // Очищаем кэш если он стал слишком большим
            if ( count( $processed_hashes ) > $hash_cache_size ) {
                // Удаляем самые старые записи
                asort( $processed_hashes );
                $processed_hashes = array_slice( $processed_hashes, -$hash_cache_size, null, true );
            }
            
            // Проверяем заголовки на наличие нашего маркера
            if ( lakend_has_our_header( $mail_data['headers'] ) ) {
                return true; // Это наше сообщение, блокируем стандартную отправку
            }
            
            // Обрабатываем сообщение
            $message = is_array( $mail_data['message'] ) ? 
                      print_r( $mail_data['message'], true ) : 
                      $mail_data['message'];
            
            $recipients = is_array( $mail_data['to'] ) ? 
                         implode( ', ', $mail_data['to'] ) : 
                         $mail_data['to'];
            
            $message .= "\n\n---\n";
            $message .= "Recipients: " . $recipients . "\n";
            
            // Отправляем через нашу систему
            lakend_send_notification(
                $mail_data['subject'],
                $message,
                'intercepted_wp_mail',
                array(
                    'original_to' => $mail_data['to'],
                    'original_headers' => $mail_data['headers'],
                    'original_attachments' => $mail_data['attachments'],
                )
            );
            
            return true; // Блокируем стандартную отправку WordPress
            
        } finally {
            // Всегда сбрасываем флаг, даже если была ошибка
            $in_progress = false;
        }
    }
    
    return $return_null;
}

// Вспомогательная функция для проверки заголовков
function lakend_has_our_header( $headers ) {
    if ( is_array( $headers ) ) {
        foreach ( $headers as $header ) {
            if ( stripos( $header, 'X-Lakend-Notifier' ) !== false ) {
                return true;
            }
        }
    } elseif ( is_string( $headers ) && stripos( $headers, 'X-Lakend-Notifier' ) !== false ) {
        return true;
    }
    
    return false;
}

/**
 * Шорткод для формы тестирования
 */
add_shortcode( 'lakend_test_notification', 'lakend_test_notification_shortcode' );
function lakend_test_notification_shortcode( $atts ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        return '';
    }
    
    $atts = shortcode_atts( array(
        'type' => 'test',
        'button_text' => __( 'Отправить тест', 'lakend-notifier' ),
    ), $atts, 'lakend_test_notification' );
    
    ob_start();
    ?>
    <div class="lakend-test-notification">
        <button type="button" 
                class="button button-primary lakend-send-test" 
                data-type="<?php echo esc_attr( $atts['type'] ); ?>">
            <?php echo esc_html( $atts['button_text'] ); ?>
        </button>
        <div class="lakend-test-result" style="margin-top: 10px; display: none;"></div>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $('.lakend-send-test').on('click', function() {
            var $button = $(this);
            var $result = $button.siblings('.lakend-test-result');
            
            $button.prop('disabled', true);
            $result.hide().empty();
            
            $.post('<?php echo admin_url( "admin-ajax.php" ); ?>', {
                action: 'lakend_send_test',
                type: $button.data('type'),
                nonce: '<?php echo wp_create_nonce( "lakend_test_nonce" ); ?>'
            }, function(response) {
                if (response.success) {
                    $result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>').show();
                } else {
                    $result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>').show();
                }
                $button.prop('disabled', false);
            }).fail(function() {
                $result.html('<div class="notice notice-error inline"><p><?php esc_html_e( "Ошибка соединения", "lakend-notifier" ); ?></p></div>').show();
                $button.prop('disabled', false);
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Добавляем ссылку на настройки в списке плагинов
 */
add_filter( 'plugin_action_links_' . LAKEND_NOTIFIER_BASENAME, 'lakend_notifier_action_links' );
function lakend_notifier_action_links( $links ) {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        admin_url( 'admin.php?page=lakend-notifier' ),  // Исправлен slug
        esc_html__( 'Settings', 'lakend-notifier' )
    );
    array_unshift( $links, $settings_link );
    return $links;
}