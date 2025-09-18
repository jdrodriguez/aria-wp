<?php
/**
 * Settings Page
 *
 * @package    Aria
 * @subpackage Aria/admin/partials
 */

// Security check
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current tab
$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';

// Define tabs
$tabs = array(
	'general'       => __( 'General', 'aria' ),
	'notifications' => __( 'Notifications', 'aria' ),
	'advanced'      => __( 'Advanced', 'aria' ),
	'privacy'       => __( 'Privacy & GDPR', 'aria' ),
	'license'       => __( 'License', 'aria' ),
);

// Handle form submission
if ( isset( $_POST['aria_settings_nonce'] ) && wp_verify_nonce( $_POST['aria_settings_nonce'], 'aria_settings_save' ) ) {
	$saved = false;
	
	switch ( $current_tab ) {
		case 'general':
			update_option( 'aria_enable_chat', isset( $_POST['enable_chat'] ) );
			update_option( 'aria_show_on_pages', isset( $_POST['show_on_pages'] ) ? $_POST['show_on_pages'] : array() );
			update_option( 'aria_hide_on_pages', sanitize_textarea_field( $_POST['hide_on_pages'] ) );
			update_option( 'aria_operating_hours', isset( $_POST['operating_hours'] ) );
			update_option( 'aria_offline_message', sanitize_textarea_field( $_POST['offline_message'] ) );
			update_option( 'aria_require_email', isset( $_POST['require_email'] ) );
			update_option( 'aria_auto_open_delay', intval( $_POST['auto_open_delay'] ) );
			$saved = true;
			break;
			
		case 'notifications':
			$notification_settings = array(
				'enabled'                    => isset( $_POST['notifications_enabled'] ),
				'notify_admin'               => isset( $_POST['notify_admin'] ),
				'custom_emails'              => sanitize_text_field( $_POST['custom_emails'] ),
				'notify_new_conversation'    => isset( $_POST['notify_new_conversation'] ),
				'notify_conversation_ended'  => isset( $_POST['notify_conversation_ended'] ),
				'daily_summary'              => isset( $_POST['daily_summary'] ),
				'weekly_summary'             => isset( $_POST['weekly_summary'] ),
			);
			update_option( 'aria_notification_settings', $notification_settings );
			$saved = true;
			break;
			
		case 'advanced':
			update_option( 'aria_cache_responses', isset( $_POST['cache_responses'] ) );
			update_option( 'aria_cache_duration', intval( $_POST['cache_duration'] ) );
			update_option( 'aria_rate_limit', intval( $_POST['rate_limit'] ) );
			update_option( 'aria_max_conversation_length', intval( $_POST['max_conversation_length'] ) );
			update_option( 'aria_enable_typing_indicator', isset( $_POST['enable_typing_indicator'] ) );
			update_option( 'aria_response_delay', intval( $_POST['response_delay'] ) );
			update_option( 'aria_enable_analytics', isset( $_POST['enable_analytics'] ) );
			update_option( 'aria_debug_mode', isset( $_POST['debug_mode'] ) );
			$saved = true;
			break;
			
		case 'privacy':
			update_option( 'aria_gdpr_enabled', isset( $_POST['gdpr_enabled'] ) );
			update_option( 'aria_gdpr_message', wp_kses_post( $_POST['gdpr_message'] ) );
			update_option( 'aria_privacy_policy_url', esc_url_raw( $_POST['privacy_policy_url'] ) );
			update_option( 'aria_data_retention_days', intval( $_POST['data_retention_days'] ) );
			update_option( 'aria_anonymize_ips', isset( $_POST['anonymize_ips'] ) );
			update_option( 'aria_allow_data_export', isset( $_POST['allow_data_export'] ) );
			update_option( 'aria_allow_data_deletion', isset( $_POST['allow_data_deletion'] ) );
			$saved = true;
			break;
			
		case 'license':
			$license_key = sanitize_text_field( $_POST['license_key'] );
			if ( ! empty( $license_key ) ) {
				// Validate license key (basic validation)
				$validation = array(
					'valid' => strlen( $license_key ) > 10, // Basic length check
					'message' => strlen( $license_key ) > 10 ? 'License key appears valid' : 'License key is too short'
				);
				if ( $validation['valid'] ) {
					update_option( 'aria_license_key', $license_key );
					update_option( 'aria_license_status', 'active' );
					echo '<div class="notice notice-success"><p>' . esc_html__( 'License activated successfully!', 'aria' ) . '</p></div>';
				} else {
					echo '<div class="notice notice-error"><p>' . esc_html( $validation['message'] ) . '</p></div>';
				}
			}
			break;
	}
	
	if ( $saved ) {
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved successfully!', 'aria' ) . '</p></div>';
	}
}

// Get current settings
$notification_settings = get_option( 'aria_notification_settings', array() );
$settings = array(
	// General
	'enable_chat'             => get_option( 'aria_enable_chat', true ),
	'show_on_pages'           => get_option( 'aria_show_on_pages', array( 'all' ) ),
	'hide_on_pages'           => get_option( 'aria_hide_on_pages', '' ),
	'operating_hours'         => get_option( 'aria_operating_hours', false ),
	'offline_message'         => get_option( 'aria_offline_message', __( 'Sorry, we are currently offline. Please leave a message and we\'ll get back to you.', 'aria' ) ),
	'require_email'           => get_option( 'aria_require_email', false ),
	'auto_open_delay'         => get_option( 'aria_auto_open_delay', 0 ),
	
	// Notifications
	'notifications_enabled'        => isset( $notification_settings['enabled'] ) ? $notification_settings['enabled'] : false,
	'notify_admin'                 => isset( $notification_settings['notify_admin'] ) ? $notification_settings['notify_admin'] : true,
	'custom_emails'                => isset( $notification_settings['custom_emails'] ) ? $notification_settings['custom_emails'] : '',
	'notify_new_conversation'      => isset( $notification_settings['notify_new_conversation'] ) ? $notification_settings['notify_new_conversation'] : true,
	'notify_conversation_ended'    => isset( $notification_settings['notify_conversation_ended'] ) ? $notification_settings['notify_conversation_ended'] : false,
	'daily_summary'                => isset( $notification_settings['daily_summary'] ) ? $notification_settings['daily_summary'] : false,
	'weekly_summary'               => isset( $notification_settings['weekly_summary'] ) ? $notification_settings['weekly_summary'] : false,
	
	// Advanced
	'cache_responses'         => get_option( 'aria_cache_responses', true ),
	'cache_duration'          => get_option( 'aria_cache_duration', 3600 ),
	'rate_limit'              => get_option( 'aria_rate_limit', 60 ),
	'max_conversation_length' => get_option( 'aria_max_conversation_length', 100 ),
	'enable_typing_indicator' => get_option( 'aria_enable_typing_indicator', true ),
	'response_delay'          => get_option( 'aria_response_delay', 1000 ),
	'enable_analytics'        => get_option( 'aria_enable_analytics', true ),
	'debug_mode'              => get_option( 'aria_debug_mode', false ),
	
	// Privacy
	'gdpr_enabled'            => get_option( 'aria_gdpr_enabled', false ),
	'gdpr_message'            => get_option( 'aria_gdpr_message', __( 'By using this chat, you agree to our privacy policy.', 'aria' ) ),
	'privacy_policy_url'      => get_option( 'aria_privacy_policy_url', '' ),
	'data_retention_days'     => get_option( 'aria_data_retention_days', 90 ),
	'anonymize_ips'           => get_option( 'aria_anonymize_ips', true ),
	'allow_data_export'       => get_option( 'aria_allow_data_export', true ),
	'allow_data_deletion'     => get_option( 'aria_allow_data_deletion', true ),
	
	// License
	'license_key'             => get_option( 'aria_license_key', '' ),
	'license_status'          => get_option( 'aria_license_status', 'inactive' ),
);
?>

<div class="wrap aria-settings">
	<!-- Styled with SCSS grok-inspired design system in admin.scss -->
	
	<!-- Page Header with Logo -->
	<div class="aria-page-header">
		<?php 
		// Include centralized logo component
		include ARIA_PLUGIN_PATH . 'admin/partials/components/aria-admin-logo.php';
		?>
		<div class="aria-page-info">
			<h1 class="aria-page-title"><?php esc_html_e( 'Settings', 'aria' ); ?></h1>
			<p class="aria-page-description"><?php esc_html_e( 'Configure how Aria behaves and interacts with your visitors', 'aria' ); ?></p>
		</div>
	</div>

	<div class="aria-page-content">
	
	<!-- Tabs Card -->
	<div class="aria-metrics-grid single-column">
		<div class="aria-metric-card">
			<div class="metric-header">
				<span class="metric-icon dashicons dashicons-admin-settings"></span>
				<h3><?php esc_html_e( 'Settings Categories', 'aria' ); ?></h3>
			</div>
			<div class="metric-content">
				<nav class="aria-tabs-wrapper">
					<?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=aria-settings&tab=' . $tab_key ) ); ?>" 
						   class="aria-tab <?php echo $current_tab === $tab_key ? 'aria-tab-active' : ''; ?>">
							<?php echo esc_html( $tab_label ); ?>
						</a>
					<?php endforeach; ?>
				</nav>
			</div>
		</div>
	</div>

	<div class="aria-settings-container">
		<form method="post" action="" class="aria-settings-form">
			<?php wp_nonce_field( 'aria_settings_save', 'aria_settings_nonce' ); ?>
		
		<?php if ( 'general' === $current_tab ) : ?>
			<!-- General Settings -->
			<div class="aria-metrics-grid single-column">
				<div class="aria-metric-card">
					<div class="metric-header">
						<span class="metric-icon dashicons dashicons-admin-generic"></span>
						<h3><?php esc_html_e( 'General Settings', 'aria' ); ?></h3>
					</div>
					<div class="metric-content">
						<table class="form-table aria-form-table">
					<tr>
						<th scope="row">
							<label for="enable_chat"><?php esc_html_e( 'Enable Chat', 'aria' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" 
								       name="enable_chat" 
								       id="enable_chat" 
								       value="1" 
								       <?php checked( $settings['enable_chat'] ); ?> />
								<?php esc_html_e( 'Enable Aria chat widget on your website', 'aria' ); ?>
							</label>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label><?php esc_html_e( 'Display On', 'aria' ); ?></label>
						</th>
						<td>
							<fieldset>
								<label>
									<input type="checkbox" 
									       name="show_on_pages[]" 
									       value="all" 
									       <?php checked( in_array( 'all', $settings['show_on_pages'] ) ); ?> />
									<?php esc_html_e( 'All pages', 'aria' ); ?>
								</label><br>
								
								<label>
									<input type="checkbox" 
									       name="show_on_pages[]" 
									       value="home" 
									       <?php checked( in_array( 'home', $settings['show_on_pages'] ) ); ?> />
									<?php esc_html_e( 'Homepage only', 'aria' ); ?>
								</label><br>
								
								<label>
									<input type="checkbox" 
									       name="show_on_pages[]" 
									       value="posts" 
									       <?php checked( in_array( 'posts', $settings['show_on_pages'] ) ); ?> />
									<?php esc_html_e( 'Blog posts', 'aria' ); ?>
								</label><br>
								
								<label>
									<input type="checkbox" 
									       name="show_on_pages[]" 
									       value="pages" 
									       <?php checked( in_array( 'pages', $settings['show_on_pages'] ) ); ?> />
									<?php esc_html_e( 'Static pages', 'aria' ); ?>
								</label><br>
								
								<label>
									<input type="checkbox" 
									       name="show_on_pages[]" 
									       value="products" 
									       <?php checked( in_array( 'products', $settings['show_on_pages'] ) ); ?> />
									<?php esc_html_e( 'WooCommerce products', 'aria' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="hide_on_pages"><?php esc_html_e( 'Hide On Pages', 'aria' ); ?></label>
						</th>
						<td>
							<textarea name="hide_on_pages" 
							          id="hide_on_pages" 
							          rows="3" 
							          class="large-text"><?php echo esc_textarea( $settings['hide_on_pages'] ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'Enter page URLs or IDs where Aria should not appear (one per line).', 'aria' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="operating_hours"><?php esc_html_e( 'Operating Hours', 'aria' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" 
								       name="operating_hours" 
								       id="operating_hours" 
								       value="1" 
								       <?php checked( $settings['operating_hours'] ); ?> />
								<?php esc_html_e( 'Enable operating hours', 'aria' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Configure specific hours in the schedule section below.', 'aria' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="offline_message"><?php esc_html_e( 'Offline Message', 'aria' ); ?></label>
						</th>
						<td>
							<textarea name="offline_message" 
							          id="offline_message" 
							          rows="3" 
							          class="large-text"><?php echo esc_textarea( $settings['offline_message'] ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'Message shown when chat is offline or outside operating hours.', 'aria' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="require_email"><?php esc_html_e( 'Require Email', 'aria' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" 
								       name="require_email" 
								       id="require_email" 
								       value="1" 
								       <?php checked( $settings['require_email'] ); ?> />
								<?php esc_html_e( 'Require visitors to provide email before chatting', 'aria' ); ?>
							</label>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="auto_open_delay"><?php esc_html_e( 'Auto-open Delay', 'aria' ); ?></label>
						</th>
						<td>
							<input type="number" 
							       name="auto_open_delay" 
							       id="auto_open_delay" 
							       value="<?php echo esc_attr( $settings['auto_open_delay'] ); ?>" 
							       min="0" 
							       max="300" 
							       step="5" /> <?php esc_html_e( 'seconds', 'aria' ); ?>
							<p class="description">
								<?php esc_html_e( 'Automatically open chat after this delay (0 to disable).', 'aria' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<!-- Operating Hours -->
		<?php if ( $settings['operating_hours'] ) : ?>
		<div class="aria-metric-card">
			<div class="metric-header">
				<span class="metric-icon dashicons dashicons-clock"></span>
				<h3><?php esc_html_e( 'Operating Hours Schedule', 'aria' ); ?></h3>
			</div>
			<div class="metric-content">
				<table class="form-table aria-schedule-table aria-form-table">
					<?php
					$days = array(
						'monday'    => __( 'Monday', 'aria' ),
						'tuesday'   => __( 'Tuesday', 'aria' ),
						'wednesday' => __( 'Wednesday', 'aria' ),
						'thursday'  => __( 'Thursday', 'aria' ),
						'friday'    => __( 'Friday', 'aria' ),
						'saturday'  => __( 'Saturday', 'aria' ),
						'sunday'    => __( 'Sunday', 'aria' ),
					);
					
					$schedule = get_option( 'aria_operating_schedule', array() );
					
					foreach ( $days as $day_key => $day_label ) :
						$day_schedule = isset( $schedule[ $day_key ] ) ? $schedule[ $day_key ] : array(
							'enabled' => true,
							'open'    => '09:00',
							'close'   => '17:00',
						);
					?>
					<tr>
						<th scope="row"><?php echo esc_html( $day_label ); ?></th>
						<td>
							<label>
								<input type="checkbox" 
								       name="schedule[<?php echo esc_attr( $day_key ); ?>][enabled]" 
								       value="1" 
								       <?php checked( $day_schedule['enabled'] ); ?> />
								<?php esc_html_e( 'Open', 'aria' ); ?>
							</label>
							
							<span class="aria-time-inputs" style="<?php echo ! $day_schedule['enabled'] ? 'display:none;' : ''; ?>">
								<input type="time" 
								       name="schedule[<?php echo esc_attr( $day_key ); ?>][open]" 
								       value="<?php echo esc_attr( $day_schedule['open'] ); ?>" />
								<?php esc_html_e( 'to', 'aria' ); ?>
								<input type="time" 
								       name="schedule[<?php echo esc_attr( $day_key ); ?>][close]" 
								       value="<?php echo esc_attr( $day_schedule['close'] ); ?>" />
							</span>
						</td>
					</tr>
					<?php endforeach; ?>
				</table>
			</div>
		</div>
		<?php endif; ?>
	</div>
			
		<?php elseif ( 'notifications' === $current_tab ) : ?>
			<!-- Notification Settings -->
			<div class="aria-metrics-grid single-column">
				<div class="aria-metric-card">
					<div class="metric-header">
						<span class="metric-icon dashicons dashicons-email"></span>
						<h3><?php esc_html_e( 'Email Notifications', 'aria' ); ?></h3>
					</div>
					<div class="metric-content">
						<table class="form-table aria-form-table">
					<tr>
						<th scope="row">
							<label for="notifications_enabled"><?php esc_html_e( 'Enable Notifications', 'aria' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" 
								       name="notifications_enabled" 
								       id="notifications_enabled" 
								       value="1" 
								       <?php checked( $settings['notifications_enabled'] ); ?> />
								<?php esc_html_e( 'Enable email notifications for conversations', 'aria' ); ?>
							</label>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label><?php esc_html_e( 'Recipients', 'aria' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" 
								       name="notify_admin" 
								       value="1" 
								       <?php checked( $settings['notify_admin'] ); ?> />
								<?php 
								printf( 
									esc_html__( 'Admin email (%s)', 'aria' ), 
									esc_html( get_option( 'admin_email' ) ) 
								); 
								?>
							</label>
							<br><br>
							
							<label for="custom_emails">
								<?php esc_html_e( 'Additional Recipients:', 'aria' ); ?>
							</label><br>
							<input type="text" 
							       name="custom_emails" 
							       id="custom_emails" 
							       value="<?php echo esc_attr( $settings['custom_emails'] ); ?>" 
							       class="large-text" />
							<p class="description">
								<?php esc_html_e( 'Enter additional email addresses separated by commas.', 'aria' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>
			
			<div class="aria-settings-section">
				<h2><?php esc_html_e( 'Notification Types', 'aria' ); ?></h2>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label><?php esc_html_e( 'Real-time Notifications', 'aria' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" 
								       name="notify_new_conversation" 
								       value="1" 
								       <?php checked( $settings['notify_new_conversation'] ); ?> />
								<?php esc_html_e( 'New conversation started', 'aria' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Send email when a visitor starts a new conversation.', 'aria' ); ?>
							</p>
							<br>
							
							<label>
								<input type="checkbox" 
								       name="notify_conversation_ended" 
								       value="1" 
								       <?php checked( $settings['notify_conversation_ended'] ); ?> />
								<?php esc_html_e( 'Conversation ended', 'aria' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Send full conversation transcript when a conversation ends.', 'aria' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label><?php esc_html_e( 'Summary Reports', 'aria' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" 
								       name="daily_summary" 
								       value="1" 
								       <?php checked( $settings['daily_summary'] ); ?> />
								<?php esc_html_e( 'Daily summary', 'aria' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Send daily summary of all conversations at midnight.', 'aria' ); ?>
							</p>
							<br>
							
							<label>
								<input type="checkbox" 
								       name="weekly_summary" 
								       value="1" 
								       <?php checked( $settings['weekly_summary'] ); ?> />
								<?php esc_html_e( 'Weekly summary', 'aria' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Send weekly summary every Monday morning.', 'aria' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>
			
			<div class="aria-settings-section">
				<h2><?php esc_html_e( 'Test Notifications', 'aria' ); ?></h2>
				
				<p><?php esc_html_e( 'Send a test email to verify your notification settings are working correctly.', 'aria' ); ?></p>
				
				<p>
					<!-- Styled with SCSS aria-button-secondary mixin in admin.scss -->
					<button type="button" class="button button-secondary aria-btn-secondary" id="test-notification">
						<?php esc_html_e( 'Send Test Email', 'aria' ); ?>
					</button>
					<span class="aria-test-result" style="margin-left: 10px; display: none;"></span>
				</p>
			</div>
			
		<?php elseif ( 'advanced' === $current_tab ) : ?>
			<!-- Advanced Settings -->
			<div class="aria-settings-section">
				<h2><?php esc_html_e( 'Performance', 'aria' ); ?></h2>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="cache_responses"><?php esc_html_e( 'Cache Responses', 'aria' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" 
								       name="cache_responses" 
								       id="cache_responses" 
								       value="1" 
								       <?php checked( $settings['cache_responses'] ); ?> />
								<?php esc_html_e( 'Cache similar questions to improve performance', 'aria' ); ?>
							</label>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="cache_duration"><?php esc_html_e( 'Cache Duration', 'aria' ); ?></label>
						</th>
						<td>
							<input type="number" 
							       name="cache_duration" 
							       id="cache_duration" 
							       value="<?php echo esc_attr( $settings['cache_duration'] ); ?>" 
							       min="300" 
							       max="86400" 
							       step="300" /> <?php esc_html_e( 'seconds', 'aria' ); ?>
							<p class="description">
								<?php esc_html_e( 'How long to cache responses (default: 3600 seconds = 1 hour).', 'aria' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="rate_limit"><?php esc_html_e( 'Rate Limit', 'aria' ); ?></label>
						</th>
						<td>
							<input type="number" 
							       name="rate_limit" 
							       id="rate_limit" 
							       value="<?php echo esc_attr( $settings['rate_limit'] ); ?>" 
							       min="10" 
							       max="300" /> <?php esc_html_e( 'messages per hour', 'aria' ); ?>
							<p class="description">
								<?php esc_html_e( 'Maximum messages per visitor per hour.', 'aria' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>

			<div class="aria-settings-section">
				<h2><?php esc_html_e( 'Behavior', 'aria' ); ?></h2>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="max_conversation_length"><?php esc_html_e( 'Max Conversation Length', 'aria' ); ?></label>
						</th>
						<td>
							<input type="number" 
							       name="max_conversation_length" 
							       id="max_conversation_length" 
							       value="<?php echo esc_attr( $settings['max_conversation_length'] ); ?>" 
							       min="10" 
							       max="500" /> <?php esc_html_e( 'messages', 'aria' ); ?>
							<p class="description">
								<?php esc_html_e( 'Maximum messages in a single conversation.', 'aria' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="enable_typing_indicator"><?php esc_html_e( 'Typing Indicator', 'aria' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" 
								       name="enable_typing_indicator" 
								       id="enable_typing_indicator" 
								       value="1" 
								       <?php checked( $settings['enable_typing_indicator'] ); ?> />
								<?php esc_html_e( 'Show typing indicator when Aria is responding', 'aria' ); ?>
							</label>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="response_delay"><?php esc_html_e( 'Response Delay', 'aria' ); ?></label>
						</th>
						<td>
							<input type="number" 
							       name="response_delay" 
							       id="response_delay" 
							       value="<?php echo esc_attr( $settings['response_delay'] ); ?>" 
							       min="0" 
							       max="5000" 
							       step="100" /> <?php esc_html_e( 'milliseconds', 'aria' ); ?>
							<p class="description">
								<?php esc_html_e( 'Artificial delay to make responses feel more natural.', 'aria' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>

			<div class="aria-settings-section">
				<h2><?php esc_html_e( 'Developer Options', 'aria' ); ?></h2>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="enable_analytics"><?php esc_html_e( 'Analytics', 'aria' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" 
								       name="enable_analytics" 
								       id="enable_analytics" 
								       value="1" 
								       <?php checked( $settings['enable_analytics'] ); ?> />
								<?php esc_html_e( 'Enable conversation analytics and insights', 'aria' ); ?>
							</label>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="debug_mode"><?php esc_html_e( 'Debug Mode', 'aria' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" 
								       name="debug_mode" 
								       id="debug_mode" 
								       value="1" 
								       <?php checked( $settings['debug_mode'] ); ?> />
								<?php esc_html_e( 'Enable debug logging (for troubleshooting)', 'aria' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Warning: This may expose sensitive information in logs.', 'aria' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>
			
		<?php elseif ( 'privacy' === $current_tab ) : ?>
			<!-- Privacy Settings -->
			<div class="aria-settings-section">
				<h2><?php esc_html_e( 'GDPR Compliance', 'aria' ); ?></h2>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="gdpr_enabled"><?php esc_html_e( 'Enable GDPR Features', 'aria' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" 
								       name="gdpr_enabled" 
								       id="gdpr_enabled" 
								       value="1" 
								       <?php checked( $settings['gdpr_enabled'] ); ?> />
								<?php esc_html_e( 'Enable GDPR compliance features', 'aria' ); ?>
							</label>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="gdpr_message"><?php esc_html_e( 'Consent Message', 'aria' ); ?></label>
						</th>
						<td>
							<textarea name="gdpr_message" 
							          id="gdpr_message" 
							          rows="3" 
							          class="large-text"><?php echo esc_textarea( $settings['gdpr_message'] ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'Message shown to visitors before starting a conversation.', 'aria' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="privacy_policy_url"><?php esc_html_e( 'Privacy Policy URL', 'aria' ); ?></label>
						</th>
						<td>
							<input type="url" 
							       name="privacy_policy_url" 
							       id="privacy_policy_url" 
							       value="<?php echo esc_url( $settings['privacy_policy_url'] ); ?>" 
							       class="large-text" />
							<p class="description">
								<?php esc_html_e( 'Link to your privacy policy page.', 'aria' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>

			<div class="aria-settings-section">
				<h2><?php esc_html_e( 'Data Management', 'aria' ); ?></h2>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="data_retention_days"><?php esc_html_e( 'Data Retention', 'aria' ); ?></label>
						</th>
						<td>
							<input type="number" 
							       name="data_retention_days" 
							       id="data_retention_days" 
							       value="<?php echo esc_attr( $settings['data_retention_days'] ); ?>" 
							       min="1" 
							       max="365" /> <?php esc_html_e( 'days', 'aria' ); ?>
							<p class="description">
								<?php esc_html_e( 'Automatically delete conversations older than this.', 'aria' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="anonymize_ips"><?php esc_html_e( 'Anonymize IPs', 'aria' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" 
								       name="anonymize_ips" 
								       id="anonymize_ips" 
								       value="1" 
								       <?php checked( $settings['anonymize_ips'] ); ?> />
								<?php esc_html_e( 'Anonymize visitor IP addresses', 'aria' ); ?>
							</label>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label><?php esc_html_e( 'User Rights', 'aria' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" 
								       name="allow_data_export" 
								       value="1" 
								       <?php checked( $settings['allow_data_export'] ); ?> />
								<?php esc_html_e( 'Allow users to export their data', 'aria' ); ?>
							</label><br>
							
							<label>
								<input type="checkbox" 
								       name="allow_data_deletion" 
								       value="1" 
								       <?php checked( $settings['allow_data_deletion'] ); ?> />
								<?php esc_html_e( 'Allow users to request data deletion', 'aria' ); ?>
							</label>
						</td>
					</tr>
				</table>
			</div>
			
		<?php elseif ( 'license' === $current_tab ) : ?>
			<!-- License Settings -->
			<div class="aria-settings-section">
				<h2><?php esc_html_e( 'License Information', 'aria' ); ?></h2>
				
				<?php
				// Get license info using admin class
				$admin = new Aria_Admin( 'aria', ARIA_VERSION );
				$license_method = new ReflectionMethod( 'Aria_Admin', 'get_license_status' );
				$license_method->setAccessible( true );
				$license_info = $license_method->invoke( $admin );
				?>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label><?php esc_html_e( 'Current Status', 'aria' ); ?></label>
						</th>
						<td>
							<?php if ( 'active' === $license_info['status'] ) : ?>
								<span class="aria-license-status active">
									<span class="dashicons dashicons-yes-alt"></span>
									<?php esc_html_e( 'Active', 'aria' ); ?>
								</span>
							<?php elseif ( 'trial' === $license_info['status'] ) : ?>
								<span class="aria-license-status trial">
									<span class="dashicons dashicons-clock"></span>
									<?php 
									printf( 
										esc_html__( 'Trial - %d days remaining', 'aria' ), 
										$license_info['days_remaining'] 
									); 
									?>
								</span>
							<?php else : ?>
								<span class="aria-license-status inactive">
									<span class="dashicons dashicons-warning"></span>
									<?php esc_html_e( 'Inactive', 'aria' ); ?>
								</span>
							<?php endif; ?>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="license_key"><?php esc_html_e( 'License Key', 'aria' ); ?></label>
						</th>
						<td>
							<input type="text" 
							       name="license_key" 
							       id="license_key" 
							       value="<?php echo esc_attr( $settings['license_key'] ); ?>" 
							       class="regular-text" 
							       placeholder="<?php esc_attr_e( 'Enter your license key', 'aria' ); ?>" />
							
							<?php if ( ! empty( $settings['license_key'] ) ) : ?>
								<!-- Styled with SCSS aria-button-secondary mixin in admin.scss -->
								<button type="button" class="button button-secondary aria-btn-secondary" id="deactivate-license">
									<?php esc_html_e( 'Deactivate', 'aria' ); ?>
								</button>
							<?php endif; ?>
							
							<p class="description">
								<?php 
								printf(
									esc_html__( 'Enter your license key to unlock all features. %s', 'aria' ),
									'<a href="https://ariaplugin.com/account" target="_blank">' . esc_html__( 'Get your license key', 'aria' ) . '</a>'
								);
								?>
							</p>
						</td>
					</tr>
					
					<?php if ( 'active' === $license_info['status'] && isset( $license_info['expires'] ) ) : ?>
					<tr>
						<th scope="row">
							<label><?php esc_html_e( 'Expires', 'aria' ); ?></label>
						</th>
						<td>
							<?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $license_info['expires'] ) ) ); ?>
						</td>
					</tr>
					<?php endif; ?>
				</table>
			</div>

			<div class="aria-settings-section">
				<h2><?php esc_html_e( 'Plan Features', 'aria' ); ?></h2>
				
				<div class="aria-plan-comparison">
					<div class="aria-plan-card">
						<h3><?php esc_html_e( 'Free Trial', 'aria' ); ?></h3>
						<ul>
							<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( '30 days trial', 'aria' ); ?></li>
							<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'All features included', 'aria' ); ?></li>
							<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Up to 1,000 conversations', 'aria' ); ?></li>
							<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Basic support', 'aria' ); ?></li>
						</ul>
					</div>
					
					<div class="aria-plan-card featured">
						<h3><?php esc_html_e( 'Pro License', 'aria' ); ?></h3>
						<ul>
							<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Unlimited conversations', 'aria' ); ?></li>
							<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Priority AI responses', 'aria' ); ?></li>
							<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Advanced analytics', 'aria' ); ?></li>
							<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Premium support', 'aria' ); ?></li>
							<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'White-label options', 'aria' ); ?></li>
						</ul>
						<!-- Styled with SCSS aria-button-primary mixin in admin.scss -->
						<a href="https://ariaplugin.com/pricing" target="_blank" class="button button-primary aria-btn-primary">
							<?php esc_html_e( 'Upgrade Now', 'aria' ); ?>
						</a>
					</div>
				</div>
			</div>
		<?php endif; ?>
		
			<p class="submit">
				<!-- Styled with SCSS aria-button-primary mixin in admin.scss -->
				<button type="submit" class="button button-primary aria-btn-primary">
					<span class="dashicons dashicons-saved"></span>
					<?php esc_html_e( 'Save Settings', 'aria' ); ?>
				</button>
			</p>
		</form>
	</div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	// Operating hours toggle
	$('.aria-schedule-table input[type="checkbox"]').on('change', function() {
		var timeInputs = $(this).closest('tr').find('.aria-time-inputs');
		if ($(this).is(':checked')) {
			timeInputs.show();
		} else {
			timeInputs.hide();
		}
	});
	
	// Show on pages logic
	$('input[name="show_on_pages[]"][value="all"]').on('change', function() {
		if ($(this).is(':checked')) {
			$('input[name="show_on_pages[]"]:not([value="all"])').prop('checked', false).prop('disabled', true);
		} else {
			$('input[name="show_on_pages[]"]:not([value="all"])').prop('disabled', false);
		}
	}).trigger('change');
	
	// License deactivation
	$('#deactivate-license').on('click', function() {
		if (confirm('<?php echo esc_js( __( 'Are you sure you want to deactivate your license?', 'aria' ) ); ?>')) {
			// Handle license deactivation
			$('#license_key').val('');
			$(this).closest('form').submit();
		}
	});
	
	// Test notification
	$('#test-notification').on('click', function() {
		var button = $(this);
		var result = $('.aria-test-result');
		
		button.prop('disabled', true);
		result.text('<?php echo esc_js( __( 'Sending test email...', 'aria' ) ); ?>').show();
		
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'aria_test_notification',
				nonce: '<?php echo wp_create_nonce( 'aria_test_notification' ); ?>'
			},
			success: function(response) {
				if (response.success) {
					result.html('<span style="color: green;"><?php echo esc_js( __( 'Test email sent successfully!', 'aria' ) ); ?></span>');
				} else {
					result.html('<span style="color: red;">' + response.data.message + '</span>');
				}
			},
			error: function() {
				result.html('<span style="color: red;"><?php echo esc_js( __( 'Failed to send test email.', 'aria' ) ); ?></span>');
			},
			complete: function() {
				button.prop('disabled', false);
				setTimeout(function() {
					result.fadeOut();
				}, 5000);
			}
		});
	});
});
</script>

