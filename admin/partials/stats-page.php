<?php
/**
 * Страница статистики
 * 
 * @package LakendNotifier
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap lakend-notifier-wrap">
    <div class="lakend-notifier-header">
        <h1>
            <span class="dashicons dashicons-chart-line"></span>
            <?php esc_html_e( 'Statistics', 'lakend-notifier' ); ?>
        </h1>
        <p><?php esc_html_e( 'Sending statistics and performance metrics', 'lakend-notifier' ); ?></p>
    </div>
    
    <div style="text-align: right; margin-bottom: 20px;">
        <button type="button" class="button" id="lakend-refresh-stats">
            <span class="dashicons dashicons-update"></span>
            <?php esc_html_e( 'Refresh', 'lakend-notifier' ); ?>
        </button>
    </div>
    
    <div id="lakend-stats-container">
        <div class="lakend-stats-grid">
            <div class="lakend-stat-card">
                <h3><?php esc_html_e( 'Today', 'lakend-notifier' ); ?></h3>
                <div class="lakend-stat-number"><?php echo esc_html( $stats_today->total ?: 0 ); ?></div>
                <div class="lakend-stat-label"><?php esc_html_e( 'Total', 'lakend-notifier' ); ?></div>
                <div class="lakend-stat-success"><?php echo esc_html( $stats_today->successful ?: 0 ); ?> <?php esc_html_e( 'Successful', 'lakend-notifier' ); ?></div>
                <div class="lakend-stat-failed"><?php echo esc_html( $stats_today->failed ?: 0 ); ?> <?php esc_html_e( 'Failed', 'lakend-notifier' ); ?></div>
            </div>
            
            <div class="lakend-stat-card">
                <h3><?php esc_html_e( 'Last 7 days', 'lakend-notifier' ); ?></h3>
                <div class="lakend-stat-number"><?php echo esc_html( $stats_7days->total ?: 0 ); ?></div>
                <div class="lakend-stat-label"><?php esc_html_e( 'Total', 'lakend-notifier' ); ?></div>
                <div class="lakend-stat-success"><?php echo esc_html( $stats_7days->successful ?: 0 ); ?> <?php esc_html_e( 'Successful', 'lakend-notifier' ); ?></div>
                <div class="lakend-stat-failed"><?php echo esc_html( $stats_7days->failed ?: 0 ); ?> <?php esc_html_e( 'Failed', 'lakend-notifier' ); ?></div>
            </div>
            
            <div class="lakend-stat-card">
                <h3><?php esc_html_e( 'Last 30 days', 'lakend-notifier' ); ?></h3>
                <div class="lakend-stat-number"><?php echo esc_html( $stats_30days->total ?: 0 ); ?></div>
                <div class="lakend-stat-label"><?php esc_html_e( 'Total', 'lakend-notifier' ); ?></div>
                <div class="lakend-stat-success"><?php echo esc_html( $stats_30days->successful ?: 0 ); ?> <?php esc_html_e( 'Successful', 'lakend-notifier' ); ?></div>
                <div class="lakend-stat-failed"><?php echo esc_html( $stats_30days->failed ?: 0 ); ?> <?php esc_html_e( 'Failed', 'lakend-notifier' ); ?></div>
            </div>
            
            <div class="lakend-stat-card">
                <h3><?php esc_html_e( 'All time', 'lakend-notifier' ); ?></h3>
                <div class="lakend-stat-number"><?php echo esc_html( $stats_all->total ?: 0 ); ?></div>
                <div class="lakend-stat-label"><?php esc_html_e( 'Total', 'lakend-notifier' ); ?></div>
                <div class="lakend-stat-success"><?php echo esc_html( $stats_all->successful ?: 0 ); ?> <?php esc_html_e( 'Successful', 'lakend-notifier' ); ?></div>
                <div class="lakend-stat-failed"><?php echo esc_html( $stats_all->failed ?: 0 ); ?> <?php esc_html_e( 'Failed', 'lakend-notifier' ); ?></div>
            </div>
        </div>
        
        <?php if ( $stats_all->total > 0 ) : ?>
            <?php
            // Формируем данные для графика (последние 14 дней)
            global $wpdb;
            $table_name = $wpdb->prefix . 'lakend_notifier_logs';
            
            $chart_data = $wpdb->get_results( $wpdb->prepare(
                "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as total,
                    SUM(success) as successful,
                    SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed
                FROM {$table_name}
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC"
            ) );
            
            $labels = array();
            $successful = array();
            $failed = array();
            
            foreach ( $chart_data as $data ) {
                $labels[] = date_i18n( 'M j', strtotime( $data->date ) );
                $successful[] = $data->successful;
                $failed[] = $data->failed;
            }
            ?>
            
            <div class="lakend-chart-container">
                <h3><?php esc_html_e( 'Sending Statistics', 'lakend-notifier' ); ?></h3>
                <canvas id="lakend-stats-chart" width="400" height="200"></canvas>
                <input type="hidden" id="lakend-chart-data" value="<?php echo esc_attr( json_encode( array(
                    'labels' => $labels,
                    'successful' => $successful,
                    'failed' => $failed
                ) ) ); ?>">
            </div>
        <?php else : ?>
            <p><?php esc_html_e( 'No statistics data', 'lakend-notifier' ); ?></p>
        <?php endif; ?>
    </div>
</div>