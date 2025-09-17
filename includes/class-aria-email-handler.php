<?php
/**
 * Email notification handler for Aria
 *
 * @package    Aria
 * @subpackage Aria/includes
 */

/**
 * Email notification handler class.
 */
class Aria_Email_Handler {

	/**
	 * Send conversation notification email
	 *
	 * @param array $conversation_data Conversation details.
	 * @return bool Whether email was sent successfully.
	 */
	public function send_conversation_notification( $conversation_data ) {
		// Get notification settings
		$notification_settings = get_option( 'aria_notification_settings', array() );
		
		// Check if notifications are enabled
		if ( empty( $notification_settings['enabled'] ) ) {
			return false;
		}
		
		// Get recipient emails
		$recipients = $this->get_recipient_emails( $notification_settings );
		if ( empty( $recipients ) ) {
			return false;
		}
		
		// Prepare email data
		$subject = $this->get_email_subject( $conversation_data );
		$message = $this->get_email_message( $conversation_data );
		$headers = $this->get_email_headers();
		
		// Send email
		return wp_mail( $recipients, $subject, $message, $headers );
	}
	
	/**
	 * Send new conversation started notification
	 *
	 * @param int    $conversation_id Conversation ID.
	 * @param string $user_name User's name.
	 * @param string $user_email User's email.
	 * @param string $initial_message Initial message.
	 * @return bool Whether email was sent successfully.
	 */
	public function send_new_conversation_notification( $conversation_id, $user_name, $user_email, $initial_message ) {
		$notification_settings = get_option( 'aria_notification_settings', array() );
		
		if ( empty( $notification_settings['enabled'] ) || empty( $notification_settings['notify_new_conversation'] ) ) {
			return false;
		}
		
		$recipients = $this->get_recipient_emails( $notification_settings );
		if ( empty( $recipients ) ) {
			return false;
		}
		
		$subject = sprintf(
			'[%s] New conversation started by %s',
			get_bloginfo( 'name' ),
			$user_name
		);
		
		$message = $this->format_new_conversation_email( array(
			'conversation_id' => $conversation_id,
			'user_name'       => $user_name,
			'user_email'      => $user_email,
			'initial_message' => $initial_message,
			'page_url'        => wp_get_referer() ?: home_url(),
			'timestamp'       => current_time( 'mysql' ),
		) );
		
		$headers = $this->get_email_headers();
		
		return wp_mail( $recipients, $subject, $message, $headers );
	}
	
	/**
	 * Send conversation ended notification
	 *
	 * @param int   $conversation_id Conversation ID.
	 * @param array $conversation_data Full conversation data.
	 * @return bool Whether email was sent successfully.
	 */
	public function send_conversation_ended_notification( $conversation_id, $conversation_data ) {
		$notification_settings = get_option( 'aria_notification_settings', array() );
		
		if ( empty( $notification_settings['enabled'] ) || empty( $notification_settings['notify_conversation_ended'] ) ) {
			return false;
		}
		
		$recipients = $this->get_recipient_emails( $notification_settings );
		if ( empty( $recipients ) ) {
			return false;
		}
		
		$subject = sprintf(
			'[%s] Conversation ended - %s',
			get_bloginfo( 'name' ),
			$conversation_data['guest_name']
		);
		
		$message = $this->format_conversation_summary_email( $conversation_id, $conversation_data );
		$headers = $this->get_email_headers();
		
		return wp_mail( $recipients, $subject, $message, $headers );
	}
	
	/**
	 * Get recipient email addresses
	 *
	 * @param array $settings Notification settings.
	 * @return array List of email addresses.
	 */
	public function get_recipient_emails( $settings ) {
		$recipients = array();
		
		// Add admin email if enabled
		if ( ! empty( $settings['notify_admin'] ) ) {
			$recipients[] = get_option( 'admin_email' );
		}
		
		// Add custom emails
		if ( ! empty( $settings['custom_emails'] ) ) {
			$custom_emails = array_map( 'trim', explode( ',', $settings['custom_emails'] ) );
			$custom_emails = array_filter( $custom_emails, 'is_email' );
			$recipients = array_merge( $recipients, $custom_emails );
		}
		
		return array_unique( $recipients );
	}
	
	/**
	 * Get email subject
	 *
	 * @param array $conversation_data Conversation data.
	 * @return string Email subject.
	 */
	private function get_email_subject( $conversation_data ) {
		return sprintf(
			'[%s] Aria Conversation: %s',
			get_bloginfo( 'name' ),
			$conversation_data['user_name']
		);
	}
	
	/**
	 * Get email headers
	 *
	 * @return array Email headers.
	 */
	private function get_email_headers() {
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>',
		);
		
		return $headers;
	}
	
	/**
	 * Format new conversation email
	 *
	 * @param array $data Conversation data.
	 * @return string Formatted email message.
	 */
	public function format_new_conversation_email( $data ) {
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<style>
				body {
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
					line-height: 1.6;
					color: #333;
					background-color: #f4f4f4;
					margin: 0;
					padding: 0;
				}
				.container {
					max-width: 600px;
					margin: 20px auto;
					background-color: #ffffff;
					border-radius: 8px;
					overflow: hidden;
					box-shadow: 0 2px 4px rgba(0,0,0,0.1);
				}
				.header {
					background-color: #2271b1;
					color: white;
					padding: 20px;
					text-align: center;
				}
				.header h1 {
					margin: 0;
					font-size: 24px;
				}
				.content {
					padding: 30px;
				}
				.info-grid {
					display: grid;
					grid-template-columns: 150px 1fr;
					gap: 15px;
					margin-bottom: 30px;
				}
				.info-label {
					font-weight: bold;
					color: #666;
				}
				.message-box {
					background-color: #f8f9fa;
					border-left: 4px solid #2271b1;
					padding: 15px;
					margin: 20px 0;
					border-radius: 4px;
				}
				.message-label {
					font-weight: bold;
					color: #666;
					margin-bottom: 10px;
				}
				.footer {
					background-color: #f8f9fa;
					padding: 20px;
					text-align: center;
					color: #666;
					font-size: 14px;
				}
				.button {
					display: inline-block;
					padding: 10px 20px;
					background-color: #2271b1;
					color: white;
					text-decoration: none;
					border-radius: 4px;
					margin-top: 20px;
				}
			</style>
		</head>
		<body>
			<div class="container">
				<div class="header">
					<h1>New Conversation Started</h1>
				</div>
				<div class="content">
					<div class="info-grid">
						<div class="info-label">Name:</div>
						<div><?php echo esc_html( $data['user_name'] ); ?></div>
						
						<div class="info-label">Email:</div>
						<div><a href="mailto:<?php echo esc_attr( $data['user_email'] ); ?>"><?php echo esc_html( $data['user_email'] ); ?></a></div>
						
						<div class="info-label">Page:</div>
						<div><a href="<?php echo esc_url( $data['page_url'] ); ?>"><?php echo esc_html( $data['page_url'] ); ?></a></div>
						
						<div class="info-label">Started:</div>
						<div><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $data['timestamp'] ) ) ); ?></div>
						
						<div class="info-label">Conversation ID:</div>
						<div>#<?php echo esc_html( $data['conversation_id'] ); ?></div>
					</div>
					
					<div class="message-box">
						<div class="message-label">Initial Message:</div>
						<div><?php echo nl2br( esc_html( $data['initial_message'] ) ); ?></div>
					</div>
					
					<p style="text-align: center;">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=aria-conversations&conversation_id=' . $data['conversation_id'] ) ); ?>" class="button">View Conversation</a>
					</p>
				</div>
				<div class="footer">
					<p>This notification was sent from <?php echo esc_html( get_bloginfo( 'name' ) ); ?> via Aria Chat Assistant.</p>
				</div>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}
	
	/**
	 * Format conversation summary email
	 *
	 * @param int   $conversation_id Conversation ID.
	 * @param array $data Conversation data.
	 * @return string Formatted email message.
	 */
	private function format_conversation_summary_email( $conversation_id, $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_conversations';
		
		// Get full conversation history
		$conversation = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE id = %d",
				$conversation_id
			),
			ARRAY_A
		);
		
		if ( ! $conversation ) {
			return '';
		}
		
		$messages = json_decode( $conversation['conversation_log'], true );
		if ( ! is_array( $messages ) ) {
			$messages = array();
		}
		
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<style>
				body {
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
					line-height: 1.6;
					color: #333;
					background-color: #f4f4f4;
					margin: 0;
					padding: 0;
				}
				.container {
					max-width: 700px;
					margin: 20px auto;
					background-color: #ffffff;
					border-radius: 8px;
					overflow: hidden;
					box-shadow: 0 2px 4px rgba(0,0,0,0.1);
				}
				.header {
					background-color: #2271b1;
					color: white;
					padding: 20px;
					text-align: center;
				}
				.header h1 {
					margin: 0;
					font-size: 24px;
				}
				.content {
					padding: 30px;
				}
				.info-grid {
					display: grid;
					grid-template-columns: 150px 1fr;
					gap: 15px;
					margin-bottom: 30px;
					background-color: #f8f9fa;
					padding: 20px;
					border-radius: 8px;
				}
				.info-label {
					font-weight: bold;
					color: #666;
				}
				.conversation-thread {
					margin: 30px 0;
				}
				.message {
					margin-bottom: 20px;
					padding: 15px;
					border-radius: 8px;
				}
				.message-user {
					background-color: #e3f2fd;
					margin-left: 50px;
				}
				.message-aria {
					background-color: #f5f5f5;
					margin-right: 50px;
				}
				.message-header {
					font-weight: bold;
					margin-bottom: 5px;
					color: #666;
					font-size: 14px;
				}
				.message-time {
					font-size: 12px;
					color: #999;
					margin-left: 10px;
				}
				.stats-grid {
					display: grid;
					grid-template-columns: repeat(3, 1fr);
					gap: 20px;
					margin: 30px 0;
				}
				.stat-box {
					background-color: #f8f9fa;
					padding: 20px;
					border-radius: 8px;
					text-align: center;
				}
				.stat-number {
					font-size: 28px;
					font-weight: bold;
					color: #2271b1;
				}
				.stat-label {
					color: #666;
					font-size: 14px;
				}
				.footer {
					background-color: #f8f9fa;
					padding: 20px;
					text-align: center;
					color: #666;
					font-size: 14px;
				}
				.button {
					display: inline-block;
					padding: 10px 20px;
					background-color: #2271b1;
					color: white;
					text-decoration: none;
					border-radius: 4px;
				}
			</style>
		</head>
		<body>
			<div class="container">
				<div class="header">
					<h1>Conversation Summary</h1>
				</div>
				<div class="content">
					<div class="info-grid">
						<div class="info-label">Name:</div>
						<div><?php echo esc_html( $conversation['guest_name'] ); ?></div>
						
						<div class="info-label">Email:</div>
						<div><a href="mailto:<?php echo esc_attr( $conversation['guest_email'] ); ?>"><?php echo esc_html( $conversation['guest_email'] ); ?></a></div>
						
						<div class="info-label">Started:</div>
						<div><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $conversation['created_at'] ) ) ); ?></div>
						
						<div class="info-label">Duration:</div>
						<div><?php echo esc_html( $this->calculate_duration( $conversation['created_at'], $conversation['updated_at'] ) ); ?></div>
						
						<div class="info-label">Status:</div>
						<div><?php echo esc_html( ucfirst( $conversation['status'] ) ); ?></div>
						
						<div class="info-label">Rating:</div>
						<div><?php echo $conversation['satisfaction_rating'] ? esc_html( $conversation['satisfaction_rating'] ) . '/5' : 'Not rated'; ?></div>
					</div>
					
					<div class="stats-grid">
						<div class="stat-box">
							<div class="stat-number"><?php echo count( $messages ); ?></div>
							<div class="stat-label">Total Messages</div>
						</div>
						<div class="stat-box">
							<div class="stat-number"><?php echo count( array_filter( $messages, function( $m ) { return $m['sender'] === 'user'; } ) ); ?></div>
							<div class="stat-label">User Messages</div>
						</div>
						<div class="stat-box">
							<div class="stat-number"><?php echo count( array_filter( $messages, function( $m ) { return $m['sender'] === 'aria'; } ) ); ?></div>
							<div class="stat-label">Aria Responses</div>
						</div>
					</div>
					
					<h2 style="margin-top: 40px; color: #333;">Conversation Thread</h2>
					<div class="conversation-thread">
						<?php foreach ( $messages as $message ) : ?>
							<div class="message message-<?php echo esc_attr( $message['sender'] ); ?>">
								<div class="message-header">
									<?php echo $message['sender'] === 'user' ? esc_html( $conversation['guest_name'] ) : 'Aria'; ?>
									<span class="message-time"><?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $message['timestamp'] ) ) ); ?></span>
								</div>
								<div><?php echo nl2br( esc_html( $message['content'] ) ); ?></div>
							</div>
						<?php endforeach; ?>
					</div>
					
					<p style="text-align: center; margin-top: 40px;">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=aria-conversations&conversation_id=' . $conversation_id ) ); ?>" class="button">View in Admin</a>
					</p>
				</div>
				<div class="footer">
					<p>This conversation summary was sent from <?php echo esc_html( get_bloginfo( 'name' ) ); ?> via Aria Chat Assistant.</p>
					<p style="font-size: 12px;">To manage notification settings, go to Aria → Settings in your WordPress admin.</p>
				</div>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}
	
	/**
	 * Calculate conversation duration
	 *
	 * @param string $start Start time.
	 * @param string $end End time.
	 * @return string Formatted duration.
	 */
	private function calculate_duration( $start, $end ) {
		$start_time = strtotime( $start );
		$end_time = strtotime( $end );
		$duration = $end_time - $start_time;
		
		if ( $duration < 60 ) {
			return $duration . ' seconds';
		} elseif ( $duration < 3600 ) {
			return round( $duration / 60 ) . ' minutes';
		} else {
			$hours = floor( $duration / 3600 );
			$minutes = round( ( $duration % 3600 ) / 60 );
			return $hours . ' hours ' . $minutes . ' minutes';
		}
	}
	
	/**
	 * Send daily conversation summary
	 *
	 * @return bool Whether email was sent successfully.
	 */
	public function send_daily_summary() {
		$notification_settings = get_option( 'aria_notification_settings', array() );
		
		if ( empty( $notification_settings['enabled'] ) || empty( $notification_settings['daily_summary'] ) ) {
			return false;
		}
		
		$recipients = $this->get_recipient_emails( $notification_settings );
		if ( empty( $recipients ) ) {
			return false;
		}
		
		// Get yesterday's conversations
		global $wpdb;
		$table = $wpdb->prefix . 'aria_conversations';
		
		$yesterday = date( 'Y-m-d', strtotime( '-1 day' ) );
		$conversations = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table 
				WHERE DATE(created_at) = %s 
				ORDER BY created_at DESC",
				$yesterday
			),
			ARRAY_A
		);
		
		if ( empty( $conversations ) ) {
			return false; // No conversations to report
		}
		
		$subject = sprintf(
			'[%s] Daily Aria Conversation Summary - %s',
			get_bloginfo( 'name' ),
			date_i18n( get_option( 'date_format' ), strtotime( $yesterday ) )
		);
		
		$message = $this->format_daily_summary_email( $conversations, $yesterday );
		$headers = $this->get_email_headers();
		
		return wp_mail( $recipients, $subject, $message, $headers );
	}
	
	/**
	 * Format daily summary email
	 *
	 * @param array  $conversations List of conversations.
	 * @param string $date Date for the summary.
	 * @return string Formatted email message.
	 */
	private function format_daily_summary_email( $conversations, $date ) {
		$total_messages = 0;
		$rated_count = 0;
		$total_rating = 0;
		
		foreach ( $conversations as $conv ) {
			$messages = json_decode( $conv['conversation_log'], true );
			if ( is_array( $messages ) ) {
				$total_messages += count( $messages );
			}
			
			if ( $conv['satisfaction_rating'] ) {
				$rated_count++;
				$total_rating += intval( $conv['satisfaction_rating'] );
			}
		}
		
		$avg_rating = $rated_count > 0 ? round( $total_rating / $rated_count, 1 ) : 0;
		
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<style>
				body {
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
					line-height: 1.6;
					color: #333;
					background-color: #f4f4f4;
					margin: 0;
					padding: 0;
				}
				.container {
					max-width: 700px;
					margin: 20px auto;
					background-color: #ffffff;
					border-radius: 8px;
					overflow: hidden;
					box-shadow: 0 2px 4px rgba(0,0,0,0.1);
				}
				.header {
					background-color: #2271b1;
					color: white;
					padding: 20px;
					text-align: center;
				}
				.header h1 {
					margin: 0;
					font-size: 24px;
				}
				.content {
					padding: 30px;
				}
				.stats-grid {
					display: grid;
					grid-template-columns: repeat(4, 1fr);
					gap: 20px;
					margin: 30px 0;
				}
				.stat-box {
					background-color: #f8f9fa;
					padding: 20px;
					border-radius: 8px;
					text-align: center;
				}
				.stat-number {
					font-size: 32px;
					font-weight: bold;
					color: #2271b1;
				}
				.stat-label {
					color: #666;
					font-size: 14px;
				}
				.conversation-list {
					margin-top: 30px;
				}
				.conversation-item {
					border-bottom: 1px solid #eee;
					padding: 20px 0;
				}
				.conversation-item:last-child {
					border-bottom: none;
				}
				.conversation-header {
					display: flex;
					justify-content: space-between;
					align-items: start;
					margin-bottom: 10px;
				}
				.conversation-user {
					font-weight: bold;
					color: #333;
				}
				.conversation-time {
					color: #666;
					font-size: 14px;
				}
				.conversation-snippet {
					color: #666;
					font-style: italic;
					margin-top: 5px;
				}
				.footer {
					background-color: #f8f9fa;
					padding: 20px;
					text-align: center;
					color: #666;
					font-size: 14px;
				}
				.button {
					display: inline-block;
					padding: 10px 20px;
					background-color: #2271b1;
					color: white;
					text-decoration: none;
					border-radius: 4px;
				}
			</style>
		</head>
		<body>
			<div class="container">
				<div class="header">
					<h1>Daily Conversation Summary</h1>
					<p style="margin: 10px 0 0; opacity: 0.9;"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date ) ) ); ?></p>
				</div>
				<div class="content">
					<div class="stats-grid">
						<div class="stat-box">
							<div class="stat-number"><?php echo count( $conversations ); ?></div>
							<div class="stat-label">Conversations</div>
						</div>
						<div class="stat-box">
							<div class="stat-number"><?php echo $total_messages; ?></div>
							<div class="stat-label">Total Messages</div>
						</div>
						<div class="stat-box">
							<div class="stat-number"><?php echo $rated_count; ?></div>
							<div class="stat-label">Ratings</div>
						</div>
						<div class="stat-box">
							<div class="stat-number"><?php echo $avg_rating; ?>/5</div>
							<div class="stat-label">Avg Rating</div>
						</div>
					</div>
					
					<h2 style="margin-top: 40px; margin-bottom: 20px; color: #333;">Conversation Details</h2>
					<div class="conversation-list">
						<?php foreach ( $conversations as $conv ) : 
							$messages = json_decode( $conv['conversation_log'], true );
							$first_message = ! empty( $messages ) ? $messages[0]['content'] : '';
						?>
							<div class="conversation-item">
								<div class="conversation-header">
									<div>
										<div class="conversation-user">
											<?php echo esc_html( $conv['guest_name'] ); ?>
											<?php if ( $conv['satisfaction_rating'] ) : ?>
												<span style="color: #f39c12; margin-left: 10px;">
													<?php echo str_repeat( '★', intval( $conv['satisfaction_rating'] ) ); ?>
												</span>
											<?php endif; ?>
										</div>
										<div style="color: #666; font-size: 14px; margin-top: 5px;">
											<?php echo esc_html( $conv['guest_email'] ); ?>
										</div>
									</div>
									<div class="conversation-time">
										<?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $conv['created_at'] ) ) ); ?>
									</div>
								</div>
								<div class="conversation-snippet">
									"<?php echo esc_html( substr( $first_message, 0, 150 ) ); ?><?php echo strlen( $first_message ) > 150 ? '...' : ''; ?>"
								</div>
								<div style="margin-top: 10px;">
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=aria-conversations&conversation_id=' . $conv['id'] ) ); ?>" style="color: #2271b1; text-decoration: none; font-size: 14px;">View conversation →</a>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
					
					<p style="text-align: center; margin-top: 40px;">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=aria-conversations' ) ); ?>" class="button">View All Conversations</a>
					</p>
				</div>
				<div class="footer">
					<p>This daily summary was sent from <?php echo esc_html( get_bloginfo( 'name' ) ); ?> via Aria Chat Assistant.</p>
					<p style="font-size: 12px;">To manage notification settings, go to Aria → Settings in your WordPress admin.</p>
				</div>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}
}