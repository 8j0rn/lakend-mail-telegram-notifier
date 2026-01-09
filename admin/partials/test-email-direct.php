<?php
/**
 * Прямой тест отправки почты
 * 
 * @package LakendNotifier
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Только для администраторов
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

// Получаем настройки
$options = get_option('lakend_notifier_settings', array());
$from_email = !empty($options['email_from_address']) ? $options['email_from_address'] : get_option('admin_email');
$from_name = !empty($options['email_from_name']) ? $options['email_from_name'] : get_bloginfo('name');
$recipient = get_option('admin_email');

?>
<div class="wrap">
    <h1>Direct Email Test</h1>
    
    <div class="card">
        <h2>Test Configuration</h2>
        <ul>
            <li><strong>From:</strong> <?php echo esc_html($from_name); ?> &lt;<?php echo esc_html($from_email); ?>&gt;</li>
            <li><strong>To:</strong> <?php echo esc_html($recipient); ?></li>
            <li><strong>WordPress Version:</strong> <?php echo esc_html(get_bloginfo('version')); ?></li>
            <li><strong>PHP Version:</strong> <?php echo esc_html(PHP_VERSION); ?></li>
        </ul>
        
        <h3>Test 1: Simple wp_mail()</h3>
        <form method="post">
            <input type="hidden" name="test_action" value="simple_wp_mail">
            <?php wp_nonce_field('lakend_direct_test'); ?>
            <button type="submit" class="button button-primary">Send Simple Test</button>
        </form>
        
        <h3>Test 2: With Headers</h3>
        <form method="post">
            <input type="hidden" name="test_action" value="with_headers">
            <?php wp_nonce_field('lakend_direct_test'); ?>
            <button type="submit" class="button button-primary">Send with Headers</button>
        </form>
        
        <h3>Test 3: Test PHPMailer Configuration</h3>
        <form method="post">
            <input type="hidden" name="test_action" value="test_phpmailer">
            <?php wp_nonce_field('lakend_direct_test'); ?>
            <button type="submit" class="button button-primary">Test PHPMailer</button>
        </form>
    </div>
    
    <div class="card">
        <h2>Results</h2>
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('lakend_direct_test')) {
            $test_action = sanitize_text_field($_POST['test_action']);
            
            echo '<div class="notice notice-info"><pre>';
            
            switch ($test_action) {
                case 'simple_wp_mail':
                    echo "Test: Simple wp_mail()\n";
                    echo "To: {$recipient}\n";
                    echo "Subject: Direct Test Email\n";
                    
                    $result = wp_mail($recipient, 'Direct Test Email', 'This is a direct test email from Lakend Notifier.');
                    echo "Result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
                    
                    global $phpmailer;
                    if (isset($phpmailer)) {
                        echo "\nPHPMailer Info:\n";
                        echo "Mailer: " . $phpmailer->Mailer . "\n";
                        echo "From: " . $phpmailer->From . "\n";
                        echo "Error: " . (is_wp_error($phpmailer->ErrorInfo) ? $phpmailer->ErrorInfo->get_error_message() : 'None') . "\n";
                    }
                    break;
                    
                case 'with_headers':
                    echo "Test: wp_mail() with Headers\n";
                    $headers = array(
                        'Content-Type: text/plain; charset=UTF-8',
                        'From: ' . $from_name . ' <' . $from_email . '>',
                        'Reply-To: ' . $from_name . ' <' . $from_email . '>'
                    );
                    
                    $result = wp_mail($recipient, 'Direct Test with Headers', 'This is a test with proper headers.', $headers);
                    echo "Result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
                    
                    global $phpmailer;
                    if (isset($phpmailer)) {
                        echo "\nPHPMailer Info:\n";
                        echo "Mailer: " . $phpmailer->Mailer . "\n";
                        echo "From: " . $phpmailer->From . "\n";
                        echo "FromName: " . $phpmailer->FromName . "\n";
                        echo "Error: " . (is_wp_error($phpmailer->ErrorInfo) ? $phpmailer->ErrorInfo->get_error_message() : 'None') . "\n";
                    }
                    break;
                    
                case 'test_phpmailer':
                    echo "Test: PHPMailer Configuration\n\n";
                    
                    // Проверяем наличие и версию PHPMailer
                    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                        echo "PHPMailer (namespaced) is available\n";
                    } elseif (class_exists('PHPMailer')) {
                        echo "PHPMailer (old style) is available\n";
                    } else {
                        echo "PHPMailer NOT found!\n";
                    }
                    
                    // Проверяем SMTP настройки
                    echo "\nSMTP Configuration:\n";
                    echo "SMTP Server: " . ini_get('SMTP') . "\n";
                    echo "SMTP Port: " . ini_get('smtp_port') . "\n";
                    echo "sendmail_from: " . ini_get('sendmail_from') . "\n";
                    
                    // Проверяем функцию mail()
                    echo "\nmail() function: " . (function_exists('mail') ? 'Available' : 'Not available') . "\n";
                    
                    // Проверяем наличие SMTP плагинов
                    echo "\nActive SMTP Plugins:\n";
                    $active_plugins = get_option('active_plugins');
                    foreach ($active_plugins as $plugin) {
                        if (stripos($plugin, 'smtp') !== false || stripos($plugin, 'mail') !== false) {
                            echo "- " . $plugin . "\n";
                        }
                    }
                    break;
            }
            
            echo '</pre></div>';
        }
        ?>
    </div>
    
    <div class="card">
        <h2>Quick Diagnostics</h2>
        <form method="post">
            <input type="hidden" name="test_action" value="check_server">
            <?php wp_nonce_field('lakend_direct_test'); ?>
            <button type="submit" class="button">Check Server Configuration</button>
        </form>
        
        <?php
        if (isset($_POST['test_action']) && $_POST['test_action'] === 'check_server') {
            echo '<div class="notice notice-info"><pre>';
            
            // Проверяем серверные настройки
            echo "Server Info:\n";
            echo "PHP Version: " . PHP_VERSION . "\n";
            echo "PHP Mail Function: " . (function_exists('mail') ? 'Enabled' : 'Disabled') . "\n";
            echo "PHP Memory Limit: " . ini_get('memory_limit') . "\n";
            
            // Проверяем open_basedir ограничения
            $open_basedir = ini_get('open_basedir');
            echo "open_basedir: " . ($open_basedir ? $open_basedir : 'None') . "\n";
            
            // Проверяем safe_mode
            echo "safe_mode: " . (ini_get('safe_mode') ? 'On' : 'Off') . "\n";
            
            // Проверяем disable_functions
            $disabled = ini_get('disable_functions');
            echo "Disabled functions: " . ($disabled ? $disabled : 'None') . "\n";
            
            echo '</pre></div>';
        }
        ?>
    </div>
</div>