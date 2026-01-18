<?php
/**
 * Канал отправки Email (упрощенный)
 * 
 * @package LakendNotifier
 */

if ( ! class_exists( 'Lakend_Notifier_Channel_Email' ) ) {

    class Lakend_Notifier_Channel_Email {
        
        /**
         * Отправка через Email
         */
        public function send( $subject, $message, $type, $data ) {
            // Загружаем PHPMailer принудительно
            $this->load_phpmailer();
            
            $options = get_option( 'lakend_notifier_settings', array() );
            
            // Получаем получателей
            $recipients = $this->get_recipients( $type );
            
            if ( empty( $recipients ) ) {
                throw new Exception( 'Email recipients not specified' );
            }
            
            // Получаем настройки отправителя
            $from_email = ! empty( $options['email_from_address'] ) ? 
                $options['email_from_address'] : 
                get_option( 'admin_email' );
                
            $from_name = ! empty( $options['email_from_name'] ) ? 
                $options['email_from_name'] : 
                get_bloginfo( 'name' );

            // Добавляем специальный заголовок
            $headers = array(
                'Content-Type: text/plain; charset=UTF-8',
                'From: ' . $from_name . ' <' . $from_email . '>',
                'Reply-To: ' . $from_name . ' <' . $from_email . '>',
                'X-Lakend-Notifier: v1/' . LAKEND_NOTIFIER_VERSION,
            );
            
            // Для тестовых сообщений добавляем дополнительный маркер
            if ( strpos( $type, 'test' ) === 0 ) {
                $headers[] = 'X-Lakend-Type: test';
            }
            
            // Временно отключаем наш фильтр
            $has_filter = has_filter( 'pre_wp_mail', 'lakend_intercept_wp_mail' );
            if ( $has_filter ) {
                remove_filter( 'pre_wp_mail', 'lakend_intercept_wp_mail', 999 );
            }
            
            // Простое текстовое сообщение
            $body = strip_tags( $message ) . "\n\n---\nSent from: " . get_bloginfo( 'name' ) . "\nURL: " . home_url();
            
            // Сохраняем отладочную информацию
            $debug_info = array(
                'recipients' => $recipients,
                'subject' => $subject,
                'from_email' => $from_email,
                'from_name' => $from_name,
                'headers' => $headers,
                'body_length' => strlen($body),
                'type' => $type,
                'time' => current_time('mysql')
            );
            
            try {
                // Логируем перед отправкой
                error_log('Lakend Notifier: Attempting to send email: ' . print_r($debug_info, true));
                
                // Настраиваем PHPMailer перед отправкой
                add_action('phpmailer_init', array($this, 'configure_phpmailer'), 9999);
                
                $result = wp_mail( $recipients, $subject, $body, $headers );
                
                // Логируем результат
                error_log('Lakend Notifier: wp_mail result: ' . ($result ? 'true' : 'false'));
                
                // Получаем детальную информацию от PHPMailer
                global $phpmailer;
                if (isset($phpmailer)) {
                    $this->log_phpmailer_info($phpmailer, $result);
                }
                
                return $result;
            } catch ( Exception $e ) {
                error_log('Lakend Notifier: Exception during email sending: ' . $e->getMessage());
                error_log('Lakend Notifier: Exception trace: ' . $e->getTraceAsString());
                throw $e;
            } finally {
                // Всегда возвращаем фильтр на место
                if ( $has_filter ) {
                    add_filter( 'pre_wp_mail', 'lakend_intercept_wp_mail', 999, 2 );
                }
                // Отключаем наши обработчики
                remove_action('phpmailer_init', array($this, 'configure_phpmailer'), 9999);
            }
        }
        
        /**
         * Принудительная загрузка PHPMailer
         */
        private function load_phpmailer() {
            // Проверяем, загружен ли уже PHPMailer
            if (!class_exists('PHPMailer\PHPMailer\PHPMailer', false)) {
                // Подключаем PHPMailer через WordPress
                if (!function_exists('wp_mail')) {
                    // Подключаем необходимые файлы WordPress
                    if (!function_exists('_wp_mail')) {
                        require_once ABSPATH . WPINC . '/pluggable.php';
                    }
                }
                
                // Проверяем еще раз
                if (!class_exists('PHPMailer\PHPMailer\PHPMailer', false)) {
                    error_log('Lakend Notifier: PHPMailer still not loaded. Trying manual load.');
                    
                    // Пытаемся загрузить вручную
                    $phpmailer_path = ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
                    if (file_exists($phpmailer_path)) {
                        require_once $phpmailer_path;
                        require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
                        require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
                    } else {
                        // Старая версия WordPress
                        $phpmailer_path = ABSPATH . WPINC . '/class-phpmailer.php';
                        if (file_exists($phpmailer_path)) {
                            require_once $phpmailer_path;
                            require_once ABSPATH . WPINC . '/class-smtp.php';
                        }
                    }
                }
            }
            
            // Проверяем глобальную переменную
            global $phpmailer;
            if (!isset($phpmailer) || !is_object($phpmailer) || !is_a($phpmailer, 'PHPMailer')) {
                error_log('Lakend Notifier: Initializing new PHPMailer instance');
                $phpmailer = $this->initialize_phpmailer();
            }
        }
        
        /**
         * Инициализация PHPMailer
         */
        private function initialize_phpmailer() {
            // Используем namespaced версию если доступна
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
            } else {
                $mailer = new PHPMailer(true);
            }
            
            // Настраиваем кодировку
            $mailer->CharSet = 'UTF-8';
            
            return $mailer;
        }
        
        /**
         * Настройка PHPMailer
         */
        public function configure_phpmailer($phpmailer) {
            error_log('Lakend Notifier: Configuring PHPMailer');
            
            // Настраиваем SMTP если нужно
            $options = get_option( 'lakend_notifier_settings', array() );
            
            // Проверяем, нужно ли использовать SMTP
            if (!empty($options['use_smtp']) && $options['use_smtp']) {
                $this->configure_smtp($phpmailer, $options);
            } else {
                // Используем PHP mail() но с улучшенными настройками
                $phpmailer->isMail();
                
                // Устанавливаем дополнительные параметры для mail()
                if (!empty($options['mail_extra_params'])) {
                    $phpmailer->Sendmail = $options['mail_extra_params'];
                }
            }
            
            // Всегда устанавливаем UTF-8
            $phpmailer->CharSet = 'UTF-8';
            $phpmailer->Encoding = 'base64';
            
            // Включаем отладку для тестов
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $phpmailer->SMTPDebug = 2;
                $phpmailer->Debugoutput = function($str, $level) {
                    error_log("PHPMailer [{$level}]: {$str}");
                };
            }
        }
        
        /**
         * Настройка SMTP
         */
        private function configure_smtp($phpmailer, $options) {
            $phpmailer->isSMTP();
            $phpmailer->Host = !empty($options['smtp_host']) ? $options['smtp_host'] : 'localhost';
            $phpmailer->Port = !empty($options['smtp_port']) ? $options['smtp_port'] : 25;
            
            if (!empty($options['smtp_username']) && !empty($options['smtp_password'])) {
                $phpmailer->SMTPAuth = true;
                $phpmailer->Username = $options['smtp_username'];
                $phpmailer->Password = $options['smtp_password'];
            }
            
            if (!empty($options['smtp_secure'])) {
                $phpmailer->SMTPSecure = $options['smtp_secure'];
            }
        }
        
        /**
         * Логирование информации PHPMailer
         */
        private function log_phpmailer_info($phpmailer, $result) {
            error_log('Lakend Notifier: PHPMailer Mailer type: ' . $phpmailer->Mailer);
            error_log('Lakend Notifier: PHPMailer From: ' . $phpmailer->From);
            error_log('Lakend Notifier: PHPMailer FromName: ' . $phpmailer->FromName);
            error_log('Lakend Notifier: PHPMailer CharSet: ' . $phpmailer->CharSet);
            
            // Получаем всех получателей
            $to_addresses = $phpmailer->getToAddresses();
            if (!empty($to_addresses)) {
                error_log('Lakend Notifier: PHPMailer Recipients: ' . print_r($to_addresses, true));
            }
            
            // Проверяем CC и BCC
            $cc_addresses = $phpmailer->getCcAddresses();
            if (!empty($cc_addresses)) {
                error_log('Lakend Notifier: PHPMailer CC: ' . print_r($cc_addresses, true));
            }
            
            $bcc_addresses = $phpmailer->getBccAddresses();
            if (!empty($bcc_addresses)) {
                error_log('Lakend Notifier: PHPMailer BCC: ' . print_r($bcc_addresses, true));
            }
            
            // Проверяем ошибки
            if (is_wp_error($phpmailer->ErrorInfo)) {
                error_log('Lakend Notifier: PHPMailer WP_Error: ' . $phpmailer->ErrorInfo->get_error_message());
            } elseif (!empty($phpmailer->ErrorInfo)) {
                error_log('Lakend Notifier: PHPMailer Error: ' . $phpmailer->ErrorInfo);
            }
            
            // Если отправка не удалась, пытаемся получить больше информации
            if (!$result) {
                error_log('Lakend Notifier: Email sending failed.');
                
                // Пробуем альтернативный метод
                $last_error = error_get_last();
                if ($last_error) {
                    error_log('Lakend Notifier: Last PHP error: ' . print_r($last_error, true));
                }
                
                // Проверяем, была ли попытка отправки
                if (method_exists($phpmailer, 'getSMTPInstance')) {
                    $smtp = $phpmailer->getSMTPInstance();
                    if ($smtp && method_exists($smtp, 'getError')) {
                        error_log('Lakend Notifier: SMTP Error: ' . $smtp->getError());
                    }
                }
            }
        }
        
        /**
         * Получить получателей для типа уведомления
         */
        private function get_recipients( $type ) {
            $options = get_option( 'lakend_notifier_settings', array() );
            $recipients = array();
            
            // Если указаны получатели по умолчанию
            if ( ! empty( $options['email_default_recipients'] ) ) {
                $recipients = array_map( 'trim', explode( ',', $options['email_default_recipients'] ) );
            }
            
            // Если нет получателей, используем email администратора
            if ( empty( $recipients ) ) {
                $recipients = array( get_option( 'admin_email' ) );
            }
            
            // Валидируем email адреса
            $valid_recipients = array();
            foreach ( $recipients as $email ) {
                $clean_email = sanitize_email($email);
                if ( is_email( $clean_email ) ) {
                    $valid_recipients[] = $clean_email;
                } else {
                    error_log('Lakend Notifier: Invalid email address: ' . $email);
                }
            }
            
            if (empty($valid_recipients)) {
                error_log('Lakend Notifier: No valid recipients found! Using admin email.');
                $valid_recipients = array( get_option( 'admin_email' ) );
            }
            
            return array_unique( $valid_recipients );
        }
    }

}