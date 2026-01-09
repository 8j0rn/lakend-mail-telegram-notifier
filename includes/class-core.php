<?php
/**
 * Основной класс плагина
 * 
 * @package LakendNotifier
 */

// Защита от двойного включения
if ( ! class_exists( 'Lakend_Notifier_Core' ) ) {
    class Lakend_Notifier_Core {
        
        /**
         * Экземпляр класса (Singleton)
         */
        private static $instance = null;
        
        /**
         * Зарегистрированные каналы
         */
        private $channels = array();
        
        /**
         * Получить экземпляр класса
         */
        public static function get_instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        /**
         * Конструктор
         */
        private function __construct() {
            // Инициализируем оптимизатор памяти
            $this->init_memory_optimizer();
            
            $this->setup_channels();
            $this->setup_hooks();
            $this->setup_cron();
        }
        
        /**
         * Инициализация оптимизатора памяти
         */
        private function init_memory_optimizer() {
            // Подключаем класс если существует
            $optimizer_file = LAKEND_NOTIFIER_PATH . 'includes/class-memory-optimizer.php';
            if ( file_exists( $optimizer_file ) ) {
                require_once $optimizer_file;
                Lakend_Notifier_Memory_Optimizer::init();
            }
        }
        
        /**
         * Настройка каналов
         */
        private function setup_channels() {
            // Определяем путь к файлам каналов
            $channels_path = LAKEND_NOTIFIER_PATH . 'includes/';
            
            // Файлы каналов
            $channel_files = array(
                'email'    => 'class-channel-email.php',
                'telegram' => 'class-channel-telegram.php',
            );
            
            $this->channels = array();
            
            // Загружаем каждый канал, если файл существует
            foreach ( $channel_files as $channel_name => $file_name ) {
                $file_path = $channels_path . $file_name;
                
                if ( file_exists( $file_path ) ) {
                    require_once $file_path;
                    
                    $class_name = 'Lakend_Notifier_Channel_' . ucfirst( $channel_name );
                    
                    if ( class_exists( $class_name ) ) {
                        $this->channels[ $channel_name ] = new $class_name();
                    }
                }
            }
            
            // Загружаем логгер отдельно
            $logger_file = $channels_path . 'class-logger.php';
            if ( file_exists( $logger_file ) ) {
                require_once $logger_file;
            }
            
            // Позволяем другим плагинам добавлять каналы
            $this->channels = apply_filters( 'lakend_notifier_channels', $this->channels );
        }
        
        /**
         * Настройка хуков
         */
        private function setup_hooks() {
            // Добавляем ежедневную очистку старых логов
            add_action( 'lakend_notifier_cleanup_logs', array( $this, 'cleanup_old_logs' ) );
        }
        
        /**
         * Настройка крона
         */
        private function setup_cron() {
            if ( ! wp_next_scheduled( 'lakend_notifier_cleanup_logs' ) ) {
                wp_schedule_event( time(), 'daily', 'lakend_notifier_cleanup_logs' );
            }
        }
        
        /**
         * Основной метод отправки с поддержкой двух режимов
         */
        public function send( $subject, $message, $type = 'default', $data = array() ) {
            // Увеличиваем память перед отправкой
            do_action( 'lakend_notifier_before_send' );

            $options = get_option( 'lakend_notifier_settings', array() );
            $results = array();
            
            // Получаем режим отправки
            $sending_mode = isset( $options['sending_mode'] ) ? $options['sending_mode'] : 'both';
            
            // Определяем активные каналы для этого типа
            $active_channels = $this->get_active_channels( $type );
            
            // Режим 1: Отправка в оба канала
            if ( $sending_mode === 'both' ) {
                // Отправляем через каждый активный канал
                foreach ( $active_channels as $channel_name ) {
                    if ( isset( $this->channels[ $channel_name ] ) ) {
                        $channel = $this->channels[ $channel_name ];
                        
                        try {
                            $result = $channel->send( $subject, $message, $type, $data );
                            $results[ $channel_name ] = array(
                                'success' => $result,
                                'message' => $result ? 
                                    __( 'Successfully sent', 'lakend-notifier' ) : 
                                    __( 'Sending error', 'lakend-notifier' )
                            );
                        } catch ( Exception $e ) {
                            $results[ $channel_name ] = array(
                                'success' => false,
                                'message' => $e->getMessage()
                            );
                        }
                    }
                }
            }
            // Режим 2: Отправка только на email, уведомление в Telegram
            elseif ( $sending_mode === 'email_only_notify' ) {
                $email_result = false;
                $email_message = '';
                
                // Отправляем email если канал доступен
                if ( isset( $this->channels['email'] ) && in_array( 'email', $active_channels ) ) {
                    try {
                        $email_result = $this->channels['email']->send( $subject, $message, $type, $data );
                        $email_message = $email_result ? 
                            __( 'Email successfully sent', 'lakend-notifier' ) : 
                            __( 'Email sending error', 'lakend-notifier' );
                        
                        $results['email'] = array(
                            'success' => $email_result,
                            'message' => $email_message
                        );
                    } catch ( Exception $e ) {
                        $results['email'] = array(
                            'success' => false,
                            'message' => $e->getMessage()
                        );
                    }
                }
                
                // Всегда отправляем уведомление в Telegram (если канал доступен)
                if ( isset( $this->channels['telegram'] ) && in_array( 'telegram', $active_channels ) ) {
                    $telegram_message = $this->format_telegram_notification( $subject, $message, $type, $data, $email_result, $email_message );
                    
                    try {
                        $telegram_result = $this->channels['telegram']->send( 
                            __( 'Email Notification', 'lakend-notifier' ),
                            $telegram_message,
                            'email_notification',  // Используем фиксированный тип
                            $data
                        );
                        
                        $results['telegram_notification'] = array(
                            'success' => $telegram_result,
                            'message' => $telegram_result ? 
                                __( 'Telegram notification sent', 'lakend-notifier' ) : 
                                __( 'Telegram notification error', 'lakend-notifier' )
                        );
                    } catch ( Exception $e ) {
                        $results['telegram_notification'] = array(
                            'success' => false,
                            'message' => $e->getMessage()
                        );
                    }
                }
            }
            
            // Логируем результат
            if ( ! empty( $options['enable_logging'] ) ) {
                $this->log_notification( $subject, $type, $results, $data );
            }
            
            // Вызываем действие после отправки
            do_action( 'lakend_notifier_after_send', $subject, $message, $type, $results, $data, $sending_mode );

            return $results;
        }

        /**
         * Форматирование сообщения для Telegram в режиме уведомления
         */
        private function format_telegram_notification( $subject, $message, $type, $data, $email_success, $email_message = '' ) {
            
            $notification = "📧 *" . __( 'EMAIL NOTIFICATION', 'lakend-notifier' ) . "*\n\n";
            $notification .= "📌 *" . __( 'Subject', 'lakend-notifier' ) . ":* " . $this->escape_markdown( $subject ) . "\n";
            $notification .= "📋 *" . __( 'Type', 'lakend-notifier' ) . ":* `" . $this->escape_markdown( $type ) . "`\n";
            
            // Статус отправки email
            if ( $email_success !== null ) {
                $email_status = $email_success ? 
                    '✅ ' . __( 'Email sent successfully', 'lakend-notifier' ) : 
                    '❌ ' . __( 'Email sending failed', 'lakend-notifier' );
                
                $notification .= "📊 *" . __( 'Email Status', 'lakend-notifier' ) . ":* " . $email_status . "\n";
                
                if ( ! empty( $email_message ) && ! $email_success ) {
                    $notification .= "⚠️ *" . __( 'Error', 'lakend-notifier' ) . ":* `" . $this->escape_markdown( $email_message ) . "`\n";
                }
            } else {
                $notification .= "📊 *" . __( 'Email Status', 'lakend-notifier' ) . ":* " . __( 'Email channel not available', 'lakend-notifier' ) . "\n";
            }
            
            // Добавляем время
            $notification .= "🕐 *" . __( 'Time', 'lakend-notifier' ) . ":* " . date_i18n( 'd.m.Y H:i:s' ) . "\n";
            
            // Добавляем информацию о получателях если есть
            if ( ! empty( $data['recipients'] ) ) {
                $recipients = is_array( $data['recipients'] ) ? 
                            implode( ', ', $data['recipients'] ) : 
                            $data['recipients'];
                $notification .= "👥 *" . __( 'Recipients', 'lakend-notifier' ) . ":* " . $this->escape_markdown( $recipients ) . "\n";
            }
            
            // Краткое содержание сообщения
            $short_message = wp_trim_words( strip_tags( $message ), 30, '...' );
            if ( ! empty( $short_message ) ) {
                $notification .= "\n📝 *" . __( 'Content', 'lakend-notifier' ) . ":*\n```\n" . $this->escape_markdown( $short_message ) . "\n```\n";
            }
            
            // Добавляем ссылку на сайт
            $notification .= "\n🌐 *" . __( 'Site', 'lakend-notifier' ) . ":* " . home_url();
            
            return $notification;
        }

        /**
         * Экранирование для MarkdownV2
         */
        private function escape_markdown( $text ) {
            $escape_chars = [ '_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!' ];
            foreach ( $escape_chars as $char ) {
                $text = str_replace( $char, '\\' . $char, $text );
            }
            return $text;
        }

        /**
         * Получить активные каналы для типа уведомления
         */
        private function get_active_channels( $type ) {
            $options = get_option( 'lakend_notifier_settings', array() );
            
            // Для уведомлений о email всегда включаем telegram
            if ( $type === 'email_notification' ) {
                return array( 'telegram' );
            }
            
            // Обработка тестовых типов
            if ( strpos( $type, 'test' ) === 0 || strpos( $type, 'email_only' ) === 0 || strpos( $type, 'telegram_only' ) === 0 ) {
                switch ( $type ) {
                    case 'email_only':
                    case 'test_email':
                        return array( 'email' );
                        
                    case 'telegram_only':
                    case 'test_telegram':
                        return array( 'telegram' );
                        
                    case 'both_channels':
                    case 'test_both':
                        return array( 'email', 'telegram' );
                }
            }
            
            // Проверяем есть ли настройки для этого типа
            if ( ! empty( $options['channel_mapping'][ $type ] ) ) {
                return (array) $options['channel_mapping'][ $type ];
            }
            
            // Проверяем общие настройки
            if ( ! empty( $options['default_channels'] ) ) {
                return (array) $options['default_channels'];
            }
            
            // По умолчанию используем все доступные каналы
            $available_channels = array();
            foreach ( array_keys( $this->channels ) as $channel ) {
                $available_channels[] = $channel;
            }
            
            return $available_channels;
        }
        
        /**
         * Логирование уведомления
         */
        private function log_notification( $subject, $type, $results, $data ) {
            if ( class_exists( 'Lakend_Notifier_Logger' ) ) {
                $logger = new Lakend_Notifier_Logger();
                $logger->log( $subject, $type, $results, $data );
            }
        }
        
        /**
         * Очистка старых логов
         */
        public function cleanup_old_logs() {
            global $wpdb;
            $table_name = $wpdb->prefix . 'lakend_notifier_logs';
            
            // Удаляем логи старше 30 дней
            $days = apply_filters( 'lakend_notifier_log_retention_days', 30 );
            $date = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
            
            $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE created_at < %s",
                $date
            ) );
        }
        
        /**
         * Создание таблиц в БД
         */
        public static function create_tables() {
            global $wpdb;
            $charset_collate = $wpdb->get_charset_collate();
            
            // Таблица логов
            $table_name = $wpdb->prefix . 'lakend_notifier_logs';
            
            $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                subject varchar(255) NOT NULL,
                type varchar(100) NOT NULL,
                channels text NOT NULL,
                success tinyint(1) DEFAULT 0,
                data text,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY type (type),
                KEY created_at (created_at)
            ) {$charset_collate};";
            
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );
        }
        
        /**
         * Получить статистику отправок
         */
        public function get_stats( $period = '7days' ) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'lakend_notifier_logs';
            
            $where = '';
            switch ( $period ) {
                case 'today':
                    $where = "DATE(created_at) = CURDATE()";
                    break;
                case 'yesterday':
                    $where = "DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                    break;
                case '7days':
                    $where = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case '30days':
                    $where = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
                default:
                    $where = "1=1";
            }
            
            $stats = $wpdb->get_row( $wpdb->prepare(
                "SELECT 
                    COUNT(*) as total,
                    SUM(success) as successful,
                    SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed
                FROM {$table_name}
                WHERE {$where}"
            ) );
            
            return $stats;
        }
    }
}