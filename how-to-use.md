## Пример использования в шаблоне:
 
```php
<?php
// 1. Бронирование коттеджа (отправка в оба канала)
$booking_result = lakend_send_booking_notification( array(
    'cottage_name' => 'Коттедж "Лесная Сказка"',
    'check_in' => date( 'Y-m-d', strtotime( '+3 days' ) ),
    'check_out' => date( 'Y-m-d', strtotime( '+7 days' ) ),
    'customer_name' => 'Иван Иванов',
    'customer_phone' => '+7 (999) 123-45-67',
    'customer_email' => 'ivan@example.com',
    'guests' => 4,
    'nights' => 4,
    'total_price' => 20000,
    'customer_comment' => 'Хотел бы ранний заезд в 12:00',
    'source' => 'booking_form',
) );

if ( $booking_result ) {
    echo 'Уведомление о брони отправлено!';
}

// 2. Email клиенту с уведомлением в Telegram
$email_result = lakend_send_customer_email_with_notification(
    array(
        'to' => 'client@example.com',
        'subject' => 'Подтверждение бронирования коттеджа',
        'message' => "Уважаемый Иван,\n\nВаша бронь коттеджа 'Лесная Сказка' подтверждена.\nДаты: 20.12.2024 - 24.12.2024\nСтоимость: 20 000 руб.\n\nЖдем вас!",
    ),
    array(
        'client' => 'Иван Иванов',
        'booking_id' => 'BOOK-2024-123',
    )
);

if ( $email_result ) {
    echo 'Письмо клиенту отправлено, администратор уведомлен в Telegram!';
}
?>
```

## Важно: Настройте типы уведомлений в админке плагина:

1. **Тип "booking"** - должен быть настроен на отправку в **оба канала** (both)
2. **Тип "customer_email"** - должен быть настроен на режим **email_only_notify**

Это позволит функциям работать корректно с настройками плагина.