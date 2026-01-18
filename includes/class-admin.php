<?php
/**
 * Класс админ-панели
 * 
 * @package LakendNotifier
 */

class Lakend_Notifier_Admin
{

	private static $instance = null;

	public static function get_instance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct()
	{
		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('admin_init', array($this, 'register_settings'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
	}

	public function add_admin_menu()
	{
		// Главное меню
		add_menu_page(
			__('Lakend Notifier', 'lakend-notifier'),
			__('Lakend Notifier', 'lakend-notifier'),
			'manage_options',
			'lakend-notifier',
			array($this, 'render_settings_page'),
			'dashicons-megaphone',
			80
		);

		// Подменю - Settings (та же страница что и главное меню)
		add_submenu_page(
			'lakend-notifier',
			__('Settings', 'lakend-notifier'),
			__('Settings', 'lakend-notifier'),
			'manage_options',
			'lakend-notifier',
			array($this, 'render_settings_page')
		);

		// Тестовая страница
		add_submenu_page(
			'lakend-notifier',
			__('Test Page', 'lakend-notifier'),
			__('Test Page', 'lakend-notifier'),
			'manage_options',
			'lakend-notifier-test',
			array($this, 'render_test_page')
		);

		add_submenu_page(
			'lakend-notifier',
			__('Direct Email Test', 'lakend-notifier'),
			__('Direct Email Test', 'lakend-notifier'),
			'manage_options',
			'lakend-notifier-direct-test',
			array($this, 'render_direct_test_page')
		);

		// Логи отправки
		add_submenu_page(
			'lakend-notifier',
			__('Sending Logs', 'lakend-notifier'),
			__('Sending Logs', 'lakend-notifier'),
			'manage_options',
			'lakend-notifier-logs',
			array($this, 'render_logs_page')
		);

		// Статистика
		add_submenu_page(
			'lakend-notifier',
			__('Statistics', 'lakend-notifier'),
			__('Statistics', 'lakend-notifier'),
			'manage_options',
			'lakend-notifier-stats',
			array($this, 'render_stats_page')
		);
	}

	public function register_settings()
	{
		// Регистрируем опцию
		register_setting(
			'lakend_notifier_settings',  // option group
			'lakend_notifier_settings',  // option name
			array($this, 'sanitize_settings')
		);

		// Основная секция
		add_settings_section(
			'lakend_general_section',
			__('General Settings', 'lakend-notifier'),
			array($this, 'render_general_section'),
			'lakend-notifier'  // page slug
		);

		// Email секция
		add_settings_section(
			'lakend_email_section',
			__('Email Settings', 'lakend-notifier'),
			array($this, 'render_email_section'),
			'lakend-notifier'
		);

		// Telegram секция
		add_settings_section(
			'lakend_telegram_section',
			__('Telegram Settings', 'lakend-notifier'),
			array($this, 'render_telegram_section'),
			'lakend-notifier'
		);

		// Добавляем поля
		$this->add_settings_fields();
	}

	private function add_settings_fields()
	{
		// Режим отправки
		add_settings_field(
			'sending_mode',
			__('Sending Mode', 'lakend-notifier'),
			array($this, 'render_sending_mode_field'),
			'lakend-notifier',
			'lakend_general_section'
		);

		// Маппинг типов уведомлений на каналы
		add_settings_field(
			'channel_mapping',
			__('Notification Types Mapping', 'lakend-notifier'),
			array($this, 'render_channel_mapping_field'),
			'lakend-notifier',
			'lakend_general_section' // Или создайте новую секцию
		);

		// Логирование
		add_settings_field(
			'enable_logging',
			__('Enable logging', 'lakend-notifier'),
			array($this, 'render_enable_logging_field'),
			'lakend-notifier',
			'lakend_general_section'
		);

		// Перехват всех писем
		add_settings_field(
			'intercept_all_wp_mail',
			__('Intercept all wp_mail()', 'lakend-notifier'),
			array($this, 'render_intercept_all_wp_mail_field'),
			'lakend-notifier',
			'lakend_general_section'
		);

		// SMTP настройки
		add_settings_field(
			'use_smtp',
			__('Use SMTP', 'lakend-notifier'),
			array($this, 'render_use_smtp_field'),
			'lakend-notifier',
			'lakend_email_section'
		);

		add_settings_field(
			'smtp_host',
			__('SMTP Host', 'lakend-notifier'),
			array($this, 'render_smtp_host_field'),
			'lakend-notifier',
			'lakend_email_section'
		);

		add_settings_field(
			'smtp_port',
			__('SMTP Port', 'lakend-notifier'),
			array($this, 'render_smtp_port_field'),
			'lakend-notifier',
			'lakend_email_section'
		);

		add_settings_field(
			'smtp_username',
			__('SMTP Username', 'lakend-notifier'),
			array($this, 'render_smtp_username_field'),
			'lakend-notifier',
			'lakend_email_section'
		);

		add_settings_field(
			'smtp_password',
			__('SMTP Password', 'lakend-notifier'),
			array($this, 'render_smtp_password_field'),
			'lakend-notifier',
			'lakend_email_section'
		);

		add_settings_field(
			'smtp_secure',
			__('SMTP Security', 'lakend-notifier'),
			array($this, 'render_smtp_secure_field'),
			'lakend-notifier',
			'lakend_email_section'
		);

		// Email отправителя
		add_settings_field(
			'email_from_address',
			__('Sender email', 'lakend-notifier'),
			array($this, 'render_email_from_address_field'),
			'lakend-notifier',
			'lakend_email_section'
		);

		// Имя отправителя
		add_settings_field(
			'email_from_name',
			__('Sender name', 'lakend-notifier'),
			array($this, 'render_email_from_name_field'),
			'lakend-notifier',
			'lakend_email_section'
		);

		// Получатели по умолчанию
		add_settings_field(
			'email_default_recipients',
			__('Default recipients', 'lakend-notifier'),
			array($this, 'render_email_default_recipients_field'),
			'lakend-notifier',
			'lakend_email_section'
		);

		// Telegram токен
		add_settings_field(
			'telegram_bot_token',
			__('Telegram Bot Token', 'lakend-notifier'),
			array($this, 'render_telegram_bot_token_field'),
			'lakend-notifier',
			'lakend_telegram_section'
		);

		// Telegram Chat ID
		add_settings_field(
			'telegram_chat_id',
			__('Telegram Chat ID', 'lakend-notifier'),
			array($this, 'render_telegram_chat_id_field'),
			'lakend-notifier',
			'lakend_telegram_section'
		);
	}

	// Методы рендера секций
	public function render_general_section()
	{
		echo '<p>' . esc_html__('General notification settings', 'lakend-notifier') . '</p>';
	}

	public function render_email_section()
	{
		echo '<p>' . esc_html__('Email sending settings', 'lakend-notifier') . '</p>';
	}

	public function render_telegram_section()
	{
		echo '<p>' . esc_html__('Telegram notification settings', 'lakend-notifier') . '</p>';
	}

	// Методы рендера полей
	public function render_sending_mode_field()
	{
		$options = get_option('lakend_notifier_settings', array());
		$value = isset($options['sending_mode']) ? $options['sending_mode'] : 'both';
		?>
		<label style="display: block; margin-bottom: 10px;">
			<input type="radio" name="lakend_notifier_settings[sending_mode]" value="both" <?php checked($value, 'both'); ?>>
			<?php esc_html_e('Send to both email and Telegram', 'lakend-notifier'); ?>
		</label>
		<label style="display: block;">
			<input type="radio" name="lakend_notifier_settings[sending_mode]" value="email_only_notify" <?php checked($value, 'email_only_notify'); ?>>
			<?php esc_html_e('Send to email, notify in Telegram about sending', 'lakend-notifier'); ?>
		</label>
		<p class="description">
			<?php esc_html_e('Choose how notifications should be sent', 'lakend-notifier'); ?>
		</p>
		<?php
	}

	/**
	 * Отображение поля маппинга типов уведомлений
	 */
	public function render_channel_mapping_field()
	{
		$options = get_option('lakend_notifier_settings', array());
		$mapping = !empty($options['channel_mapping']) ? $options['channel_mapping'] : array();

		// Значения по умолчанию
		$default_mappings = array(
			'booking' => array('email', 'telegram'), // Оба канала
			'customer_email' => array('email'), // Только email (телеграм по режиму)
			'test' => array('email', 'telegram'), // Оба для тестов
			'intercepted_wp_mail' => array('email', 'telegram'), // Оба для перехваченных
			'default' => array('email'), // По умолчанию только email
		);

		// Объединяем с сохраненными настройками
		foreach ($default_mappings as $type => $default_channels) {
			if (!isset($mapping[$type])) {
				$mapping[$type] = $default_channels;
			}
		}

		?>
		<div id="lakend-channel-mapping" style="max-width: 800px;">

			<div class="lakend-mapping-item"
				style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 4px;">
				<h4 style="margin-top: 0;"><?php esc_html_e('Booking Notifications', 'lakend-notifier'); ?></h4>
				<p class="description" style="margin-bottom: 10px;">
					<?php esc_html_e('Used for:', 'lakend-notifier'); ?>
					<code>lakend_send_booking_notification()</code>
				</p>
				<div style="display: flex; align-items: center; gap: 15px;">
					<input type="text" value="booking" readonly style="width: 150px; background: #f0f0f0;" class="regular-text">
					<select name="lakend_notifier_settings[channel_mapping][booking][]" multiple
						style="width: 300px; height: 80px;">
						<option value="email" <?php echo in_array('email', $mapping['booking']) ? 'selected' : ''; ?>>
							<?php esc_html_e('Email', 'lakend-notifier'); ?>
						</option>
						<option value="telegram" <?php echo in_array('telegram', $mapping['booking']) ? 'selected' : ''; ?>>
							<?php esc_html_e('Telegram', 'lakend-notifier'); ?>
						</option>
					</select>
					<span class="description">
						<?php esc_html_e('Both channels recommended', 'lakend-notifier'); ?>
					</span>
				</div>
			</div>

			<div class="lakend-mapping-item"
				style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 4px;">
				<h4 style="margin-top: 0;"><?php esc_html_e('Customer Email Notifications', 'lakend-notifier'); ?></h4>
				<p class="description" style="margin-bottom: 10px;">
					<?php esc_html_e('Used for:', 'lakend-notifier'); ?>
					<code>lakend_send_customer_email_with_notification()</code>
				</p>
				<div style="display: flex; align-items: center; gap: 15px;">
					<input type="text" value="customer_email" readonly style="width: 150px; background: #f0f0f0;"
						class="regular-text">
					<select name="lakend_notifier_settings[channel_mapping][customer_email][]" multiple
						style="width: 300px; height: 60px;">
						<option value="email" <?php echo in_array('email', $mapping['customer_email']) ? 'selected' : ''; ?>>
							<?php esc_html_e('Email', 'lakend-notifier'); ?>
						</option>
						<option value="telegram" disabled style="color: #999;">
							<?php esc_html_e('Telegram (auto based on mode)', 'lakend-notifier'); ?>
						</option>
					</select>
					<span class="description">
						<?php esc_html_e('Email sends to customer, Telegram notifies admin', 'lakend-notifier'); ?>
					</span>
				</div>
			</div>

			<div class="lakend-mapping-item"
				style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 4px;">
				<h4 style="margin-top: 0;"><?php esc_html_e('Default Notification', 'lakend-notifier'); ?></h4>
				<p class="description" style="margin-bottom: 10px;">
					<?php esc_html_e('Used for all other notifications', 'lakend-notifier'); ?>
				</p>
				<div style="display: flex; align-items: center; gap: 15px;">
					<input type="text" value="default" readonly style="width: 150px; background: #f0f0f0;" class="regular-text">
					<select name="lakend_notifier_settings[channel_mapping][default][]" multiple
						style="width: 300px; height: 80px;">
						<option value="email" <?php echo in_array('email', $mapping['default']) ? 'selected' : ''; ?>>
							<?php esc_html_e('Email', 'lakend-notifier'); ?>
						</option>
						<option value="telegram" <?php echo in_array('telegram', $mapping['default']) ? 'selected' : ''; ?>>
							<?php esc_html_e('Telegram', 'lakend-notifier'); ?>
						</option>
					</select>
					<span class="description">
						<?php esc_html_e('Fallback for unspecified types', 'lakend-notifier'); ?>
					</span>
				</div>
			</div>

			<div class="lakend-custom-types" style="margin-top: 30px;">
				<h3><?php esc_html_e('Custom Notification Types', 'lakend-notifier'); ?></h3>
				<p class="description">
					<?php esc_html_e('Add custom notification types used in your code', 'lakend-notifier'); ?>
				</p>

				<div id="lakend-custom-types-container">
					<?php
					// Показываем сохраненные кастомные типы
					foreach ($mapping as $type => $channels) {
						if (!in_array($type, array('booking', 'customer_email', 'default', 'test', 'intercepted_wp_mail'))) {
							?>
							<div class="lakend-custom-type-row"
								style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
								<input type="text" name="lakend_notifier_settings[channel_mapping_custom_type][]"
									value="<?php echo esc_attr($type); ?>"
									placeholder="<?php esc_attr_e('Type name', 'lakend-notifier'); ?>" style="width: 150px;">
								<select name="lakend_notifier_settings[channel_mapping_custom_channels][]" multiple
									style="width: 250px; height: 60px;">
									<option value="email" <?php echo in_array('email', $channels) ? 'selected' : ''; ?>>
										<?php esc_html_e('Email', 'lakend-notifier'); ?>
									</option>
									<option value="telegram" <?php echo in_array('telegram', $channels) ? 'selected' : ''; ?>>
										<?php esc_html_e('Telegram', 'lakend-notifier'); ?>
									</option>
								</select>
								<button type="button" class="button button-small lakend-remove-type">
									<?php esc_html_e('Remove', 'lakend-notifier'); ?>
								</button>
							</div>
							<?php
						}
					}
					?>
				</div>

				<button type="button" id="lakend-add-custom-type" class="button" style="margin-top: 10px;">
					<?php esc_html_e('Add Custom Type', 'lakend-notifier'); ?>
				</button>
			</div>

			<script>
				jQuery(document).ready(function ($) {
					// Добавление кастомного типа
					$('#lakend-add-custom-type').on('click', function () {
						var row = $('<div class="lakend-custom-type-row" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">' +
							'<input type="text" name="lakend_notifier_settings[channel_mapping_custom_type][]" ' +
							'placeholder="<?php esc_attr_e('Type name', 'lakend-notifier'); ?>" style="width: 150px;">' +
							'<select name="lakend_notifier_settings[channel_mapping_custom_channels][]" multiple style="width: 250px; height: 60px;">' +
							'<option value="email"><?php esc_html_e('Email', 'lakend-notifier'); ?></option>' +
							'<option value="telegram"><?php esc_html_e('Telegram', 'lakend-notifier'); ?></option>' +
							'</select>' +
							'<button type="button" class="button button-small lakend-remove-type"><?php esc_html_e('Remove', 'lakend-notifier'); ?></button>' +
							'</div>');

						$('#lakend-custom-types-container').append(row);
					});

					// Удаление кастомного типа
					$(document).on('click', '.lakend-remove-type', function () {
						$(this).closest('.lakend-custom-type-row').remove();
					});

					// Предотвращение удаления предустановленных типов
					$('input[readonly]').closest('.lakend-mapping-item').find('button').remove();
				});
			</script>

			<style>
				.lakend-mapping-item h4 {
					color: #1d2327;
				}

				select[multiple] {
					min-height: 60px;
				}
			</style>

		</div>

		<p class="description">
			<?php esc_html_e('Note: Telegram notifications for "customer_email" type are controlled by the main sending mode setting above.', 'lakend-notifier'); ?>
		</p>
		<?php
	}

	public function render_enable_logging_field()
	{
		$options = get_option('lakend_notifier_settings', array());
		$value = isset($options['enable_logging']) ? $options['enable_logging'] : 1;
		?>
		<label>
			<input type="checkbox" name="lakend_notifier_settings[enable_logging]" value="1" <?php checked($value, 1); ?>>
			<?php esc_html_e('Save history of all sent notifications', 'lakend-notifier'); ?>
		</label>
		<?php
	}

	public function render_intercept_all_wp_mail_field()
	{
		$options = get_option('lakend_notifier_settings', array());
		$value = isset($options['intercept_all_wp_mail']) ? $options['intercept_all_wp_mail'] : 0;
		?>
		<label>
			<input type="checkbox" name="lakend_notifier_settings[intercept_all_wp_mail]" value="1" <?php checked($value, 1); ?>>
			<?php esc_html_e('Automatically intercept all wp_mail() calls', 'lakend-notifier'); ?>
		</label>
		<p class="description">
			<?php esc_html_e('All standard WordPress emails will be sent through Lakend Notifier', 'lakend-notifier'); ?>
		</p>
		<?php
	}

	public function render_use_smtp_field()
	{
		$options = get_option('lakend_notifier_settings', array());
		$value = isset($options['use_smtp']) ? $options['use_smtp'] : 0;
		?>
		<label>
			<input type="checkbox" name="lakend_notifier_settings[use_smtp]" value="1" <?php checked($value, 1); ?>>
			<?php esc_html_e('Use SMTP instead of PHP mail()', 'lakend-notifier'); ?>
		</label>
		<p class="description">
			<?php esc_html_e('Enable this if PHP mail() function is not working on your server', 'lakend-notifier'); ?>
		</p>
		<?php
	}

	public function render_smtp_host_field()
	{
		$options = get_option('lakend_notifier_settings', array());
		$value = isset($options['smtp_host']) ? $options['smtp_host'] : 'localhost';
		?>
		<input type="text" name="lakend_notifier_settings[smtp_host]" value="<?php echo esc_attr($value); ?>"
			class="regular-text">
		<p class="description">
			<?php esc_html_e('SMTP server hostname (e.g., smtp.gmail.com, localhost)', 'lakend-notifier'); ?>
		</p>
		<?php
	}

	public function render_smtp_port_field()
	{
		$options = get_option('lakend_notifier_settings', array());
		$value = isset($options['smtp_port']) ? $options['smtp_port'] : '25';
		?>
		<input type="number" name="lakend_notifier_settings[smtp_port]" value="<?php echo esc_attr($value); ?>"
			class="small-text" min="1" max="65535">
		<p class="description">
			<?php esc_html_e('SMTP port (25, 465, 587, etc.)', 'lakend-notifier'); ?>
		</p>
		<?php
	}

	public function render_smtp_username_field()
	{
		$options = get_option('lakend_notifier_settings', array());
		$value = isset($options['smtp_username']) ? $options['smtp_username'] : '';
		?>
		<input type="text" name="lakend_notifier_settings[smtp_username]" value="<?php echo esc_attr($value); ?>"
			class="regular-text">
		<p class="description">
			<?php esc_html_e('SMTP username (leave empty if no authentication required)', 'lakend-notifier'); ?>
		</p>
		<?php
	}

	public function render_smtp_password_field()
	{
		$options = get_option('lakend_notifier_settings', array());
		$value = isset($options['smtp_password']) ? $options['smtp_password'] : '';
		?>
		<input type="password" name="lakend_notifier_settings[smtp_password]" value="<?php echo esc_attr($value); ?>"
			class="regular-text">
		<p class="description">
			<?php esc_html_e('SMTP password', 'lakend-notifier'); ?>
		</p>
		<?php
	}

	public function render_smtp_secure_field()
	{
		$options = get_option('lakend_notifier_settings', array());
		$value = isset($options['smtp_secure']) ? $options['smtp_secure'] : '';
		?>
		<select name="lakend_notifier_settings[smtp_secure]">
			<option value=""><?php esc_html_e('None', 'lakend-notifier'); ?></option>
			<option value="ssl" <?php selected($value, 'ssl'); ?>>SSL</option>
			<option value="tls" <?php selected($value, 'tls'); ?>>TLS</option>
		</select>
		<p class="description">
			<?php esc_html_e('Encryption type', 'lakend-notifier'); ?>
		</p>
		<?php
	}

	public function render_email_from_address_field()
	{
		$options = get_option('lakend_notifier_settings', array());
		$value = isset($options['email_from_address']) ? $options['email_from_address'] : get_option('admin_email');
		?>
		<input type="email" name="lakend_notifier_settings[email_from_address]" value="<?php echo esc_attr($value); ?>"
			class="regular-text">
		<p class="description">
			<?php esc_html_e('Email address that will be indicated as sender', 'lakend-notifier'); ?>
		</p>
		<?php
	}

	public function render_email_from_name_field()
	{
		$options = get_option('lakend_notifier_settings', array());
		$value = isset($options['email_from_name']) ? $options['email_from_name'] : get_bloginfo('name');
		?>
		<input type="text" name="lakend_notifier_settings[email_from_name]" value="<?php echo esc_attr($value); ?>"
			class="regular-text">
		<p class="description">
			<?php esc_html_e('Name that will be indicated as sender', 'lakend-notifier'); ?>
		</p>
		<?php
	}

	public function render_email_default_recipients_field()
	{
		$options = get_option('lakend_notifier_settings', array());
		$value = isset($options['email_default_recipients']) ? $options['email_default_recipients'] : get_option('admin_email');
		?>
		<input type="text" name="lakend_notifier_settings[email_default_recipients]" value="<?php echo esc_attr($value); ?>"
			class="regular-text">
		<p class="description">
			<?php esc_html_e('Default email recipients (comma separated)', 'lakend-notifier'); ?>
		</p>
		<?php
	}

	public function render_telegram_bot_token_field()
	{
		$options = get_option('lakend_notifier_settings', array());
		$value = isset($options['telegram_bot_token']) ? $options['telegram_bot_token'] : '';
		?>
		<input type="password" name="lakend_notifier_settings[telegram_bot_token]" value="<?php echo esc_attr($value); ?>"
			class="regular-text">
		<p class="description">
			<?php
			printf(
				esc_html__('Get from %s', 'lakend-notifier'),
				'<a href="https://t.me/BotFather" target="_blank">@BotFather</a>'
			);
			?>
		</p>
		<?php
	}

	public function render_telegram_chat_id_field()
	{
		$options = get_option('lakend_notifier_settings', array());
		$value = isset($options['telegram_chat_id']) ? $options['telegram_chat_id'] : '';
		?>
		<input type="text" name="lakend_notifier_settings[telegram_chat_id]" value="<?php echo esc_attr($value); ?>"
			class="regular-text">
		<p class="description">
			<?php esc_html_e('Chat or channel ID (can be obtained from @userinfobot)', 'lakend-notifier'); ?>
		</p>
		<?php
	}

	public function render_settings_page()
	{
		if (!current_user_can('manage_options')) {
			return;
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e('Lakend Notifier Settings', 'lakend-notifier'); ?></h1>

			<?php if (isset($_GET['settings-updated'])): ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e('Settings saved!', 'lakend-notifier'); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php
				settings_fields('lakend_notifier_settings');
				do_settings_sections('lakend-notifier');
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function render_test_page()
	{
		if (!current_user_can('manage_options')) {
			return;
		}

		$options = get_option('lakend_notifier_settings', array());
		$sending_mode = isset($options['sending_mode']) ? $options['sending_mode'] : 'both';
		?>
		<div class="wrap">
			<h1><?php esc_html_e('Test Page', 'lakend-notifier'); ?></h1>
			<p><?php esc_html_e('Test the notification system', 'lakend-notifier'); ?></p>

			<div class="card" style="max-width: 600px; margin: 20px 0;">
				<h2><?php esc_html_e('Quick Tests', 'lakend-notifier'); ?></h2>

				<div style="margin: 20px 0;">
					<button type="button" class="button button-primary" id="lakend-test-email">
						<?php esc_html_e('Test Email', 'lakend-notifier'); ?>
					</button>
					<button type="button" class="button button-primary" id="lakend-test-telegram">
						<?php esc_html_e('Test Telegram', 'lakend-notifier'); ?>
					</button>
					<button type="button" class="button button-primary" id="lakend-test-both">
						<?php esc_html_e('Test Both', 'lakend-notifier'); ?>
					</button>
				</div>

				<div id="lakend-test-result" style="display: none; margin-top: 15px;"></div>
			</div>

			<div class="card" style="max-width: 600px; margin: 20px 0;">
				<h2><?php esc_html_e('Current Settings', 'lakend-notifier'); ?></h2>

				<table class="form-table">
					<tr>
						<th><?php esc_html_e('Sending Mode', 'lakend-notifier'); ?>:</th>
						<td>
							<?php if ($sending_mode === 'both'): ?>
								<span style="color: green; font-weight: bold;">
									<?php esc_html_e('Send to both email and Telegram', 'lakend-notifier'); ?>
								</span>
							<?php else: ?>
								<span style="color: blue; font-weight: bold;">
									<?php esc_html_e('Send to email, notify in Telegram', 'lakend-notifier'); ?>
								</span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e('Email Sender', 'lakend-notifier'); ?>:</th>
						<td>
							<?php echo esc_html(isset($options['email_from_name']) ? $options['email_from_name'] : get_bloginfo('name')); ?>
							&lt;<?php echo esc_html(isset($options['email_from_address']) ? $options['email_from_address'] : get_option('admin_email')); ?>&gt;
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e('Telegram Status', 'lakend-notifier'); ?>:</th>
						<td>
							<?php if (!empty($options['telegram_bot_token']) && !empty($options['telegram_chat_id'])): ?>
								<span style="color: green; font-weight: bold;">✅
									<?php esc_html_e('Configured', 'lakend-notifier'); ?></span>
							<?php else: ?>
								<span style="color: red; font-weight: bold;">❌
									<?php esc_html_e('Not configured', 'lakend-notifier'); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				</table>

				<p style="margin-top: 15px;">
					<a href="<?php echo admin_url('admin.php?page=lakend-notifier'); ?>" class="button">
						<?php esc_html_e('Edit Settings', 'lakend-notifier'); ?>
					</a>
				</p>
			</div>
		</div>

		<script>
			jQuery(document).ready(function ($) {
				function sendTest(type) {
					$('#lakend-test-result').hide().empty();

					$.post('<?php echo admin_url("admin-ajax.php"); ?>', {
						action: 'lakend_send_test',
						type: type,
						nonce: '<?php echo wp_create_nonce("lakend_test_nonce"); ?>'
					}, function (response) {
						if (response.success) {
							$('#lakend-test-result').html(
								'<div class="notice notice-success"><p>' + response.data.message + '</p></div>'
							).show();
						} else {
							$('#lakend-test-result').html(
								'<div class="notice notice-error"><p>' + response.data.message + '</p></div>'
							).show();
						}
					}).fail(function () {
						$('#lakend-test-result').html(
							'<div class="notice notice-error"><p>Connection error</p></div>'
						).show();
					});
				}

				$('#lakend-test-email').on('click', function () {
					sendTest('test_email');
				});

				$('#lakend-test-telegram').on('click', function () {
					sendTest('test_telegram');
				});

				$('#lakend-test-both').on('click', function () {
					sendTest('test_both');
				});
			});
		</script>
		<?php
	}

	// Добавьте метод для рендеринга страницы:
	public function render_direct_test_page()
	{
		if (!current_user_can('manage_options')) {
			return;
		}

		require_once LAKEND_NOTIFIER_PATH . 'admin/partials/test-email-direct.php';
	}

	public function render_logs_page()
	{
		if (!current_user_can('manage_options')) {
			return;
		}

		$logger = new Lakend_Notifier_Logger();
		$logs = $logger->get_recent_logs(50);
		?>
		<div class="wrap">
			<h1><?php esc_html_e('Sending Logs', 'lakend-notifier'); ?></h1>

			<?php if (!empty($logs)): ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e('Date', 'lakend-notifier'); ?></th>
							<th><?php esc_html_e('Subject', 'lakend-notifier'); ?></th>
							<th><?php esc_html_e('Type', 'lakend-notifier'); ?></th>
							<th><?php esc_html_e('Status', 'lakend-notifier'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($logs as $log): ?>
							<tr>
								<td><?php echo esc_html($log->created_at); ?></td>
								<td><?php echo esc_html($log->subject); ?></td>
								<td><?php echo esc_html($log->type); ?></td>
								<td>
									<?php if ($log->success): ?>
										<span style="color: green;">✅ <?php esc_html_e('Success', 'lakend-notifier'); ?></span>
									<?php else: ?>
										<span style="color: red;">❌ <?php esc_html_e('Failed', 'lakend-notifier'); ?></span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else: ?>
				<p><?php esc_html_e('No logs found', 'lakend-notifier'); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	public function render_stats_page()
	{
		if (!current_user_can('manage_options')) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e('Statistics', 'lakend-notifier'); ?></h1>
			<p><?php esc_html_e('Notification statistics coming soon...', 'lakend-notifier'); ?></p>
		</div>
		<?php
	}

	public function enqueue_scripts($hook)
	{
		// Загружаем только на страницах нашего плагина
		if (strpos($hook, 'lakend-notifier') === false) {
			return;
		}

		wp_enqueue_style(
			'lakend-notifier-admin',
			LAKEND_NOTIFIER_URL . 'admin/css/admin.css',
			array(),
			LAKEND_NOTIFIER_VERSION
		);

		wp_enqueue_script(
			'lakend-notifier-admin',
			LAKEND_NOTIFIER_URL . 'admin/js/admin.js',
			array('jquery'),
			LAKEND_NOTIFIER_VERSION,
			true
		);
	}

	public function sanitize_settings($input)
	{
		$sanitized = array();

		// Режим отправки
		if (isset($input['sending_mode'])) {
			$sanitized['sending_mode'] = in_array($input['sending_mode'], array('both', 'email_only_notify'))
				? $input['sending_mode']
				: 'both';
		}

		// Логирование
		$sanitized['enable_logging'] = isset($input['enable_logging']) ? 1 : 0;

		// Перехват всех писем
		$sanitized['intercept_all_wp_mail'] = isset($input['intercept_all_wp_mail']) ? 1 : 0;

		$sanitized['use_smtp'] = isset($input['use_smtp']) ? 1 : 0;

		if (isset($input['smtp_host'])) {
			$sanitized['smtp_host'] = sanitize_text_field($input['smtp_host']);
		}

		if (isset($input['smtp_port'])) {
			$port = intval($input['smtp_port']);
			$sanitized['smtp_port'] = ($port > 0 && $port <= 65535) ? $port : 25;
		}

		if (isset($input['smtp_username'])) {
			$sanitized['smtp_username'] = sanitize_text_field($input['smtp_username']);
		}

		if (isset($input['smtp_password'])) {
			$sanitized['smtp_password'] = sanitize_text_field($input['smtp_password']);
		}

		if (isset($input['smtp_secure'])) {
			$sanitized['smtp_secure'] = in_array($input['smtp_secure'], array('', 'ssl', 'tls'))
				? $input['smtp_secure']
				: '';
		}

		// Email отправителя
		if (isset($input['email_from_address'])) {
			$email = sanitize_email($input['email_from_address']);
			$sanitized['email_from_address'] = is_email($email) ? $email : get_option('admin_email');
		}

		// Имя отправителя
		if (isset($input['email_from_name'])) {
			$sanitized['email_from_name'] = sanitize_text_field($input['email_from_name']);
		}

		// Получатели по умолчанию
		if (isset($input['email_default_recipients'])) {
			$sanitized['email_default_recipients'] = sanitize_text_field($input['email_default_recipients']);
		}

		// Telegram токен
		if (isset($input['telegram_bot_token'])) {
			$sanitized['telegram_bot_token'] = sanitize_text_field($input['telegram_bot_token']);
		}

		// Telegram Chat ID
		if (isset($input['telegram_chat_id'])) {
			$sanitized['telegram_chat_id'] = sanitize_text_field($input['telegram_chat_id']);
		}

		// Маппинг каналов
		if (!empty($input['channel_mapping'])) {
			$sanitized_mapping = array();

			foreach ($input['channel_mapping'] as $type => $channels) {
				$clean_type = sanitize_key($type);
				$clean_channels = array();

				foreach ((array) $channels as $channel) {
					if (in_array($channel, array('email', 'telegram'))) {
						$clean_channels[] = $channel;
					}
				}

				if (!empty($clean_type) && !empty($clean_channels)) {
					$sanitized_mapping[$clean_type] = array_unique($clean_channels);
				}
			}

			// Добавляем кастомные типы если есть
			if (!empty($input['channel_mapping_custom_type']) && !empty($input['channel_mapping_custom_channels'])) {
				foreach ($input['channel_mapping_custom_type'] as $index => $type) {
					$clean_type = sanitize_key($type);
					$channels = isset($input['channel_mapping_custom_channels'][$index]) ?
						(array) $input['channel_mapping_custom_channels'][$index] :
						array();

					$clean_channels = array();
					foreach ($channels as $channel) {
						if (in_array($channel, array('email', 'telegram'))) {
							$clean_channels[] = $channel;
						}
					}

					if (!empty($clean_type) && !empty($clean_channels)) {
						$sanitized_mapping[$clean_type] = array_unique($clean_channels);
					}
				}
			}

			$sanitized['channel_mapping'] = $sanitized_mapping;
		}

		return $sanitized;
	}
}