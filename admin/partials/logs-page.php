<?php
/**
 * Страница логов отправки
 * 
 * @package LakendNotifier
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$logger = new Lakend_Notifier_Logger();
$logs = $logger->get_recent_logs( 50 );
?>

<div class="wrap">
    <h1><?php esc_html_e( 'Sending Logs', 'lakend-notifier' ); ?></h1>
    
    <?php if ( ! empty( $logs ) ) : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Date', 'lakend-notifier' ); ?></th>
                    <th><?php esc_html_e( 'Subject', 'lakend-notifier' ); ?></th>
                    <th><?php esc_html_e( 'Type', 'lakend-notifier' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'lakend-notifier' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $logs as $log ) : ?>
                    <tr>
                        <td><?php echo esc_html( $log->created_at ); ?></td>
                        <td><?php echo esc_html( $log->subject ); ?></td>
                        <td><?php echo esc_html( $log->type ); ?></td>
                        <td>
                            <?php if ( $log->success ) : ?>
                                <span style="color: green;">✅ <?php esc_html_e( 'Success', 'lakend-notifier' ); ?></span>
                            <?php else : ?>
                                <span style="color: red;">❌ <?php esc_html_e( 'Failed', 'lakend-notifier' ); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p><?php esc_html_e( 'No logs found', 'lakend-notifier' ); ?></p>
    <?php endif; ?>
</div>