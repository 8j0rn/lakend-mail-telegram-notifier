<?php
/**
 * Канал отправки в Telegram
 * 
 * @package LakendNotifier
 */

class Lakend_Notifier_Channel_Telegram {
    
    /**
     * Отправка в Telegram
     */
    public function send( $subject, $message, $type, $data ) {
        $options = get_option( 'lakend_notifier_settings', array() );
        
        // Проверяем наличие токена и ID чата
        if ( empty( $options['telegram_bot_token'] ) ) {
            throw new Exception( __( 'Telegram bot token not specified', 'lakend-notifier' ) );
        }
        
        if ( empty( $options['telegram_chat_id'] ) ) {
            throw new Exception( __( 'Telegram chat ID not specified', 'lakend-notifier' ) );
        }
        
        // Форматируем сообщение
        $telegram_message = $this->format_message( $subject, $message, $type, $data );
        
        // Отправляем
        return $this->send_to_telegram( $telegram_message, $options );
    }
    
    /**
     * Форматирование сообщения для Telegram
     */
    private function format_message( $subject, $message, $type, $data ) {
        $formatted = "📢 *" . $this->escape_markdown( $subject ) . "*\n\n";
        $formatted .= $this->escape_markdown( $message ) . "\n\n";
        $formatted .= "Тип: `" . $this->escape_markdown( $type ) . "`\n";
        $formatted .= "Сайт: " . $this->escape_markdown( get_bloginfo( 'name' ) ) . "\n";
        $formatted .= "Время: " . date( 'd.m.Y H:i:s' );
        
        return $formatted;
    }
    
    /**
     * Отправка в Telegram через API
     */
    private function send_to_telegram( $message, $options ) {
        $bot_token = $options['telegram_bot_token'];
        $chat_id   = $options['telegram_chat_id'];
        
        $api_url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
        
        $response = wp_remote_post( $api_url, array(
            'timeout' => 15,
            'body'    => array(
                'chat_id'    => $chat_id,
                'text'       => $message,
                'parse_mode' => 'Markdown',
            ),
        ) );
        
        if ( is_wp_error( $response ) ) {
            throw new Exception( $response->get_error_message() );
        }
        
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        return ! empty( $body['ok'] );
    }
    
    /**
     * Экранирование символов для Markdown
     */
    private function escape_markdown( $text ) {
        $escape_chars = [ '_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!' ];
        foreach ( $escape_chars as $char ) {
            $text = str_replace( $char, '\\' . $char, $text );
        }
        return $text;
    }
}