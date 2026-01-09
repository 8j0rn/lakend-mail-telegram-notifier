<?php
/**
 * Вспомогательные функции для шаблонов
 * Упрощенная версия без шорткодов и AJAX обработчиков
 * 
 * @package LakendNotifier
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Проверка доступности плагина
 */
function lakend_notifier_available() {
    return function_exists( 'lakend_send_notification' );
}

/**
 * Отправить уведомление о бронировании коттеджа
 * Отправляет в оба канала (email и Telegram)
 * 
 * @param array $booking_data {
 *     Данные бронирования
 *     
 *     @type string $cottage_name    Название коттеджа (обязательно)
 *     @type string $check_in        Дата заезда (обязательно)
 *     @type string $check_out       Дата выезда (обязательно)
 *     @type string $customer_name   Имя клиента (обязательно)
 *     @type string $customer_phone  Телефон клиента (обязательно)
 *     @type string $customer_email  Email клиента
 *     @type int    $guests          Количество гостей
 *     @type int    $nights          Количество ночей
 *     @type float  $total_price     Общая стоимость
 *     @type string $customer_comment Комментарий клиента
 *     @type string $booking_id      ID бронирования
 *     @type string $source          Источник
 * }
 * @return array|false Результаты отправки или false при ошибке
 */
function lakend_send_booking_notification( $booking_data ) {
    if ( ! lakend_notifier_available() ) {
        trigger_error( 'Lakend Notifier plugin is not available', E_USER_WARNING );
        return false;
    }
    
    // Валидация обязательных полей
    $required_fields = array( 'cottage_name', 'check_in', 'check_out', 'customer_name', 'customer_phone' );
    
    foreach ( $required_fields as $field ) {
        if ( empty( $booking_data[ $field ] ) ) {
            trigger_error( sprintf( 'Missing required field: %s', $field ), E_USER_WARNING );
            return false;
        }
    }
    
    // Формируем тему
    $subject = sprintf( 
        __( 'New Booking: %s', 'lakend-notifier' ),
        sanitize_text_field( $booking_data['cottage_name'] )
    );
    
    // Формируем сообщение
    $message = lakend_format_booking_message( $booking_data );
    
    // Дополнительные данные
    $data = array(
        'booking_data' => $booking_data,
        'source' => ! empty( $booking_data['source'] ) ? $booking_data['source'] : 'website_template',
        'timestamp' => current_time( 'mysql' ),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
    );
    
    // Отправляем с типом 'booking' (должен быть настроен на отправку в оба канала)
    return lakend_send_notification( $subject, $message, 'booking', $data );
}

/**
 * Отправить email клиенту и уведомление в Telegram
 * Использует режим email_only_notify
 * 
 * @param array $email_data {
 *     Данные для email
 *     
 *     @type string $to           Email получателя (обязательно)
 *     @type string $subject      Тема письма (обязательно)
 *     @type string $message      Текст письма (обязательно)
 *     @type array  $headers      Дополнительные заголовки
 *     @type array  $attachments  Вложения
 * }
 * @param array $telegram_data Дополнительные данные для Telegram уведомления
 * @return array|false Результаты отправки или false при ошибке
 */
function lakend_send_customer_email_with_notification( $email_data, $telegram_data = array() ) {
    if ( ! lakend_notifier_available() ) {
        trigger_error( 'Lakend Notifier plugin is not available', E_USER_WARNING );
        return false;
    }
    
    // Валидация обязательных полей для email
    $required_fields = array( 'to', 'subject', 'message' );
    
    foreach ( $required_fields as $field ) {
        if ( empty( $email_data[ $field ] ) ) {
            trigger_error( sprintf( 'Missing required email field: %s', $field ), E_USER_WARNING );
            return false;
        }
    }
    
    // Проверяем email адрес
    if ( ! is_email( $email_data['to'] ) ) {
        trigger_error( 'Invalid email address: ' . $email_data['to'], E_USER_WARNING );
        return false;
    }
    
    // Формируем данные для отправки
    $data = array(
        'email_data' => $email_data,
        'telegram_data' => $telegram_data,
        'recipients' => array( $email_data['to'] ),
        'source' => 'customer_notification',
    );
    
    // Отправляем с типом 'customer_email'
    // В настройках плагина должен быть настроен режим 'email_only_notify' для этого типа
    return lakend_send_notification( 
        sanitize_text_field( $email_data['subject'] ), 
        $email_data['message'], 
        'customer_email', 
        $data 
    );
}

/**
 * Форматирование сообщения о бронировании
 * 
 * @param array $booking_data Данные бронирования
 * @return string Отформатированное сообщение
 */
function lakend_format_booking_message( $booking_data ) {
    $message = "📋 НОВАЯ БРОНЬ КОТТЕДЖА\n\n";
    
    // Основная информация
    $message .= "🏠 Коттедж: " . sanitize_text_field( $booking_data['cottage_name'] ) . "\n";
    $message .= "📅 Заезд: " . sanitize_text_field( $booking_data['check_in'] ) . "\n";
    $message .= "📅 Выезд: " . sanitize_text_field( $booking_data['check_out'] ) . "\n";
    
    if ( ! empty( $booking_data['nights'] ) ) {
        $message .= "🌙 Ночей: " . intval( $booking_data['nights'] ) . "\n";
    }
    
    if ( ! empty( $booking_data['guests'] ) ) {
        $message .= "👥 Гостей: " . intval( $booking_data['guests'] ) . "\n";
    }
    
    if ( ! empty( $booking_data['total_price'] ) ) {
        $message .= "💰 Стоимость: " . floatval( $booking_data['total_price'] ) . " руб.\n";
    }
    
    $message .= "\n";
    
    // Информация о клиенте
    $message .= "👤 ДАННЫЕ КЛИЕНТА\n";
    $message .= "📛 Имя: " . sanitize_text_field( $booking_data['customer_name'] ) . "\n";
    $message .= "📞 Телефон: " . sanitize_text_field( $booking_data['customer_phone'] ) . "\n";
    
    if ( ! empty( $booking_data['customer_email'] ) ) {
        $message .= "📧 Email: " . sanitize_email( $booking_data['customer_email'] ) . "\n";
    }
    
    if ( ! empty( $booking_data['customer_comment'] ) ) {
        $message .= "💬 Комментарий:\n" . sanitize_textarea_field( $booking_data['customer_comment'] ) . "\n";
    }
    
    // Дополнительная информация
    $message .= "\n";
    $message .= "📊 ИНФОРМАЦИЯ О ЗАКАЗЕ\n";
    $message .= "🆔 ID: " . ( ! empty( $booking_data['booking_id'] ) ? sanitize_text_field( $booking_data['booking_id'] ) : 'auto' ) . "\n";
    $message .= "⏰ Время заявки: " . current_time( 'd.m.Y H:i' ) . "\n";
    $message .= "🌐 Источник: " . ( ! empty( $booking_data['source'] ) ? sanitize_text_field( $booking_data['source'] ) : 'Сайт' ) . "\n";
    
    return $message;
}

/**
 * Простая функция для тестирования из шаблона
 * 
 * @param string $message Текст сообщения
 * @param string $type Тип уведомления
 * @return array|false Результаты отправки
 */
function lakend_test_from_template( $message, $type = 'test' ) {
    if ( ! lakend_notifier_available() ) {
        return false;
    }
    
    return lakend_send_notification(
        __( 'Test from Template', 'lakend-notifier' ),
        $message,
        $type,
        array( 
            'test_time' => current_time( 'mysql' ),
            'template_file' => basename( debug_backtrace()[0]['file'] ?? '' )
        )
    );
}