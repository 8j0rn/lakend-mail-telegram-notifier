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
                'X-Lakend-Notifier: v1/' . LAKEND_NOTIFIER_VERSION, // Уникальный маркер с версией
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
            $body = strip_tags( $message ) . "\n\n---\nSent from: " . get_bloginfo( 'name' );
            
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
                
                // Временно включаем отладку PHPMailer
                add_action('phpmailer_init', array($this, 'enable_phpmailer_debug'), 9999);
                
                $result = wp_mail( $recipients, $subject, $body, $headers );
                
                // Логируем результат
                error_log('Lakend Notifier: wp_mail result: ' . ($result ? 'true' : 'false'));
                
                // Получаем детальную информацию от PHPMailer
                global $phpmailer;
                if (isset($phpmailer)) {
                    error_log('Lakend Notifier: PHPMailer Mailer type: ' . $phpmailer->Mailer);
                    error_log('Lakend Notifier: PHPMailer From: ' . $phpmailer->From);
                    error_log('Lakend Notifier: PHPMailer FromName: ' . $phpmailer->FromName);
                    
                    // Получаем всех получателей
                    $to_addresses = $phpmailer->getToAddresses();
                    if (!empty($to_addresses)) {
                        error_log('Lakend Notifier: PHPMailer Recipients: ' . print_r($to_addresses, true));
                    }
                    
                    if (is_wp_error($phpmailer->ErrorInfo)) {
                        error_log('Lakend Notifier: PHPMailer error: ' . $phpmailer->ErrorInfo->get_error_message());
                    } elseif (!empty($phpmailer->ErrorInfo)) {
                        error_log('Lakend Notifier: PHPMailer error info: ' . $phpmailer->ErrorInfo);
                    }
                    
                    // Проверяем SMTP ошибки если используется SMTP
                    if ($phpmailer->Mailer == 'smtp' && !empty($phpmailer->SMTPDebug)) {
                        error_log('Lakend Notifier: SMTP Debug output available');
                    }
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
                // Отключаем отладку PHPMailer
                remove_action('phpmailer_init', array($this, 'enable_phpmailer_debug'), 9999);
            }
        }
        
        /**
         * Включаем отладку PHPMailer
         */
        public function enable_phpmailer_debug($phpmailer) {
            // Включаем отладку SMTP
            $phpmailer->SMTPDebug = 2; // 2 = client and server messages
            $phpmailer->Debugoutput = function($str, $level) {
                error_log('PHPMailer [' . $level . ']: ' . $str);
            };
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
                if ( is_email( $email ) ) {
                    $valid_recipients[] = $email;
                } else {
                    error_log('Lakend Notifier: Invalid email address: ' . $email);
                }
            }
            
            if (empty($valid_recipients)) {
                error_log('Lakend Notifier: No valid recipients found!');
            }
            
            return array_unique( $valid_recipients );
        }
    }

}