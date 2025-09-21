<?php
/**
 * Handle AJAX requests
 *
 * @package    Aria
 * @subpackage Aria/includes
 */

/**
 * Handle AJAX requests for the plugin.
 */
class Aria_Ajax_Handler {

	/**
	 * Handle send message AJAX request.
	 */
	public function handle_send_message() {
		// Verify nonce
		if ( ! check_ajax_referer( 'aria_public_nonce', 'nonce', false ) ) {
			// Log for debugging
			Aria_Logger::error( 'Aria AJAX - Nonce check failed. Expected: aria_public_nonce, Received: ' . ( isset( $_POST['nonce'] ) ? $_POST['nonce'] : 'none' ) );
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		// Get and sanitize input
		$message         = isset( $_POST['message'] ) ? sanitize_textarea_field( $_POST['message'] ) : '';
		$conversation_id = isset( $_POST['conversation_id'] ) ? intval( $_POST['conversation_id'] ) : 0;
		$session_id      = isset( $_POST['session_id'] ) ? sanitize_text_field( $_POST['session_id'] ) : '';
		$name            = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
		$email           = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
		$phone           = isset( $_POST['phone'] ) ? sanitize_text_field( $_POST['phone'] ) : '';

		if ( empty( $message ) ) {
			wp_send_json_error( array( 'message' => __( 'Message cannot be empty.', 'aria' ) ) );
		}

		// Create or get conversation
		if ( ! $conversation_id ) {
			$conversation_id = $this->get_or_create_conversation( $session_id, $name, $email, $phone, $message );
		}

		// Get AI response
		$ai_provider = $this->get_ai_provider();
		if ( ! $ai_provider ) {
			wp_send_json_error( array( 'message' => __( 'AI service is not configured.', 'aria' ) ) );
		}

		// Get relevant knowledge
		$knowledge = $this->get_relevant_knowledge( $message );
		Aria_Logger::debug( 'Aria - Knowledge retrieved: ' . ( empty( $knowledge ) ? 'EMPTY' : 'Found ' . strlen( $knowledge ) . ' characters' ) );

		// Get personality settings
		$personality = $this->get_personality_settings();
		Aria_Logger::debug( 'Aria - Business type: ' . $personality['business_type'] );

		// Build prompt
		$prompt = $this->build_prompt( $message, $knowledge, $personality );
		Aria_Logger::debug( 'Aria - Prompt length: ' . strlen( $prompt ) );
		Aria_Logger::debug( 'Aria - First 500 chars of prompt: ' . substr( $prompt, 0, 500 ) );

		try {
			// Get AI response
			$response = $ai_provider->generate_response( $prompt, $this->get_conversation_context( $conversation_id ) );

			// Save to conversation log
			$this->save_to_conversation( $conversation_id, $message, $response, 'user' );
			$this->save_to_conversation( $conversation_id, $response, $response, 'aria' );

			// Track learning data
			$this->track_learning_data( $conversation_id, $message, $response );

			wp_send_json_success( array(
				'response'        => stripslashes( $response ),
				'conversation_id' => $conversation_id,
			) );

		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => __( 'Failed to get response. Please try again.', 'aria' ) ) );
		}
	}

	/**
	 * Handle start conversation AJAX request.
	 */
	public function handle_start_conversation() {
		// Verify nonce
		if ( ! check_ajax_referer( 'aria_public_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		$page_url   = isset( $_POST['page_url'] ) ? esc_url_raw( $_POST['page_url'] ) : '';
		$page_title = isset( $_POST['page_title'] ) ? sanitize_text_field( $_POST['page_title'] ) : '';
		$name       = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
		$email      = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
		$phone      = isset( $_POST['phone'] ) ? sanitize_text_field( $_POST['phone'] ) : '';

		// Get personalized greeting message
		$greeting = Aria_Personality::get_greeting_message( $name );

		// Generate session ID
		$session_id = 'aria_' . time() . '_' . wp_generate_password( 8, false );

		// Create initial conversation if name and email provided
		$conversation_id = null;
		if ( ! empty( $name ) && ! empty( $email ) ) {
			$conversation_id = $this->get_or_create_conversation( $session_id, $name, $email, '' );
		}

		wp_send_json_success( array(
			'conversation_id' => $conversation_id,
			'greeting'        => stripslashes( $greeting ),
			'session_id'      => $session_id,
		) );
	}

	/**
	 * Handle get conversation AJAX request.
	 */
	public function handle_get_conversation() {
		// Verify nonce
		if ( ! check_ajax_referer( 'aria_public_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		$session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( $_POST['session_id'] ) : '';

		if ( empty( $session_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid session.', 'aria' ) ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'aria_conversations';

		$conversation = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE session_id = %s AND site_id = %d ORDER BY created_at DESC LIMIT 1",
				$session_id,
				get_current_blog_id()
			),
			ARRAY_A
		);

		if ( $conversation ) {
			$messages = json_decode( $conversation['conversation_log'], true );
			wp_send_json_success( array(
				'conversation_id' => $conversation['id'],
				'messages'        => $messages ? $messages : array(),
			) );
		} else {
			wp_send_json_success( array(
				'conversation_id' => null,
				'messages'        => array(),
			) );
		}
	}

	/**
	 * Handle save knowledge AJAX request.
	 */
	public function handle_save_knowledge() {
		// Verify nonce
		if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'aria' ) ) );
		}

		$id       = isset( $_POST['entry_id'] ) ? intval( $_POST['entry_id'] ) : 0;
		$title   = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$content = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
		$context = isset( $_POST['context'] ) ? wp_kses_post( wp_unslash( $_POST['context'] ) ) : '';
		$response_instructions = isset( $_POST['response_instructions'] ) ? wp_kses_post( wp_unslash( $_POST['response_instructions'] ) ) : '';
		$category = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '';
		$language = isset( $_POST['language'] ) ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : 'en';
		$priority = isset( $_POST['priority'] ) ? intval( $_POST['priority'] ) : 0;

		$raw_tags = isset( $_POST['tags'] ) ? wp_unslash( $_POST['tags'] ) : '';
		$tag_items = array_filter( array_map( 'trim', explode( ',', $raw_tags ) ) );
		$sanitized_tags = array_map( 'sanitize_text_field', $tag_items );
		$tags = implode( ', ', $sanitized_tags );

		if ( empty( $title ) || empty( $content ) ) {
			wp_send_json_error( array( 'message' => __( 'Title and content are required.', 'aria' ) ) );
		}

		$data = array(
			'title'                 => $title,
			'content'               => $content,
			'context'               => $context,
			'response_instructions' => $response_instructions,
			'category'              => $category,
			'tags'                  => $tags,
			'language'              => $language,
			'priority'              => $priority,
		);

		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-database.php';
		$result = Aria_Database::save_knowledge_entry( $data, $id );

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to save knowledge.', 'aria' ) ) );
		}
		
		$entry_id = $result;
		$entry    = Aria_Database::get_knowledge_entry( $entry_id );
		$response_entry = $entry ? $this->prepare_knowledge_entry_for_response( $entry ) : array();

		wp_send_json_success( array(
			'message'  => __( 'Knowledge saved successfully and scheduled for processing.', 'aria' ),
			'entry_id' => $entry_id,
			'entry'    => $response_entry,
		) );
	}

	/**
	 * Handle get knowledge data AJAX request.
	 */
	public function handle_get_knowledge_data() {
		// Verify nonce
		if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'aria' ) ) );
		}

		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-database.php';
		global $wpdb;
		
		$entries = Aria_Database::get_knowledge_entries(
			array(
				'orderby' => 'updated_at',
				'order'   => 'DESC',
				'limit'   => 250,
			)
		);

		$prepared_entries = array_map( array( $this, 'prepare_knowledge_entry_for_response' ), $entries );

		$table            = $wpdb->prefix . 'aria_knowledge_entries';
		$total_entries    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE site_id = %d", get_current_blog_id() ) );
		$categories_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT category) FROM {$table} WHERE site_id = %d AND category <> ''", get_current_blog_id() ) );
		$last_updated_raw = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(updated_at) FROM {$table} WHERE site_id = %d", get_current_blog_id() ) );

		$chunks_table = $wpdb->prefix . 'aria_knowledge_chunks';
		$usage_stats  = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(c.usage_count), 0)
				FROM {$chunks_table} c
				INNER JOIN {$table} e ON c.entry_id = e.id
				WHERE e.site_id = %d",
				get_current_blog_id()
			)
		);

		$last_updated = __( 'Never', 'aria' );
		if ( $last_updated_raw ) {
			$last_updated = sprintf(
				__( '%s ago', 'aria' ),
				human_time_diff( strtotime( $last_updated_raw ), current_time( 'timestamp' ) )
			);
		}

		$category_values = array_filter( array_unique( array_map( 'trim', wp_list_pluck( $prepared_entries, 'category' ) ) ) );

		wp_send_json_success( array(
			'entries'         => $prepared_entries,
			'totalEntries'    => $total_entries,
			'categories'      => $categories_count,
			'categoriesList'  => array_values( $category_values ),
			'lastUpdated'     => $last_updated,
			'usageStats'      => $usage_stats,
		) );
	}

	/**
	 * Handle delete knowledge entry AJAX request.
	 */
	public function handle_delete_knowledge_entry() {
		// Verify nonce
		if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'aria' ) ) );
		}

		$entry_id = isset( $_POST['entry_id'] ) ? intval( $_POST['entry_id'] ) : 0;

		if ( $entry_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid entry ID.', 'aria' ) ) );
		}

		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-database.php';

		$entry = Aria_Database::get_knowledge_entry( $entry_id );
		if ( ! $entry || intval( $entry['site_id'] ) !== get_current_blog_id() ) {
			wp_send_json_error( array( 'message' => __( 'Knowledge entry not found.', 'aria' ) ) );
		}

		$result = Aria_Database::delete_knowledge_entry( $entry_id );

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to delete knowledge entry.', 'aria' ) ) );
		}

		// Clear related caches
		if ( class_exists( 'Aria_Cache_Manager' ) ) {
			Aria_Cache_Manager::flush_cache_group( 'aria_responses' );
		}

		wp_send_json_success( array(
			'message' => __( 'Knowledge entry deleted successfully.', 'aria' ),
		) );
	}

	/**
	 * Handle get design settings AJAX request.
	 */
	public function handle_get_design_settings() {
		if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'aria' ) ) );
		}

		$settings  = get_option( 'aria_design_settings', array() );
		$prepared  = $this->sanitize_design_settings( $settings );
		$configured = (bool) get_option( 'aria_design_configured', false );

		wp_send_json_success(
			array(
				'settings'   => $prepared,
				'configured' => $configured,
			)
		);
	}

	/**
	 * Handle get notification settings AJAX request.
	 */
	public function handle_get_notification_settings() {
		if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'aria' ) ) );
		}

		$settings = get_option( 'aria_notification_settings', array() );
		$prepared = $this->sanitize_notification_settings( $settings );

		wp_send_json_success(
			array(
				'settings' => $prepared,
			)
		);
	}

	/**
	 * Handle save notification settings AJAX request.
	 */
	public function handle_save_notification_settings() {
		if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		$raw_settings = array(
			'enableNotifications'  => isset( $_POST['enableNotifications'] ) ? wp_unslash( $_POST['enableNotifications'] ) : '',
			'additionalRecipients' => isset( $_POST['additionalRecipients'] ) ? wp_unslash( $_POST['additionalRecipients'] ) : '',
			'newConversation'      => isset( $_POST['newConversation'] ) ? wp_unslash( $_POST['newConversation'] ) : '',
		);

		$sanitized = $this->sanitize_notification_settings( $raw_settings );

		update_option( 'aria_notification_settings', $sanitized );

		wp_send_json_success(
			array(
				'message'  => __( 'Notification settings saved successfully.', 'aria' ),
				'settings' => $sanitized,
			)
		);
	}

	/**
	 * Handle get privacy settings AJAX request.
	 */
	public function handle_get_privacy_settings() {
		if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		$settings = get_option( 'aria_privacy_settings', array() );
		$prepared = $this->sanitize_privacy_settings( $settings );

		wp_send_json_success(
			array(
				'settings' => $prepared,
			)
		);
	}

	/**
	 * Handle save privacy settings AJAX request.
	 */
	public function handle_save_privacy_settings() {
		if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		$raw_settings = array(
			'enableGDPR'        => isset( $_POST['enableGDPR'] ) ? wp_unslash( $_POST['enableGDPR'] ) : '',
			'privacyPolicyUrl' => isset( $_POST['privacyPolicyUrl'] ) ? wp_unslash( $_POST['privacyPolicyUrl'] ) : '',
			'dataRetention'    => isset( $_POST['dataRetention'] ) ? wp_unslash( $_POST['dataRetention'] ) : '',
		);

		$sanitized = $this->sanitize_privacy_settings( $raw_settings );

		update_option( 'aria_privacy_settings', $sanitized );
		update_option( 'aria_privacy_enabled', $sanitized['enableGDPR'] );

		wp_send_json_success(
			array(
				'message'  => __( 'Privacy settings saved successfully.', 'aria' ),
				'settings' => $sanitized,
			)
		);
	}

	/**
	 * Handle get license settings AJAX request.
	 */
	public function handle_get_license_settings() {
		if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		$settings = get_option( 'aria_license_settings', array() );
		$prepared = $this->sanitize_license_settings( $settings );

		wp_send_json_success(
			array(
				'settings' => $prepared,
			)
		);
	}

	/**
	 * Handle license activation AJAX request.
	 */
	public function handle_activate_license() {
		if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		$license_key = isset( $_POST['licenseKey'] ) ? sanitize_text_field( wp_unslash( $_POST['licenseKey'] ) ) : '';
		if ( empty( $license_key ) ) {
			wp_send_json_error( array( 'message' => __( 'License key is required.', 'aria' ) ) );
		}

		// TODO: Replace with real remote validation when available.
		// Currently we simulate activation success if key matches pattern.
		if ( ! preg_match( '/^[A-Z0-9\-]{10,}$/', $license_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid license key format.', 'aria' ) ) );
		}

		$now = current_time( 'mysql' );
		$license_data = array(
			'licenseKey'    => $license_key,
			'licenseStatus' => 'active',
			'activatedAt'   => $now,
		);

		update_option( 'aria_license_settings', $license_data );

		wp_send_json_success(
			array(
				'message' => __( 'License activated successfully.', 'aria' ),
				'settings' => $license_data,
			)
		);
	}

	/**
	 * Handle save design settings AJAX request.
	 */
	public function handle_save_design_settings() {
		if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'aria' ) ) );
		}

			$raw_settings = array(
				'position'        => isset( $_POST['position'] ) ? wp_unslash( $_POST['position'] ) : '',
				'size'            => isset( $_POST['size'] ) ? wp_unslash( $_POST['size'] ) : '',
				'theme'           => isset( $_POST['theme'] ) ? wp_unslash( $_POST['theme'] ) : '',
				'primaryColor'    => isset( $_POST['primaryColor'] ) ? wp_unslash( $_POST['primaryColor'] ) : '',
				'backgroundColor' => isset( $_POST['backgroundColor'] ) ? wp_unslash( $_POST['backgroundColor'] ) : '',
				'textColor'       => isset( $_POST['textColor'] ) ? wp_unslash( $_POST['textColor'] ) : '',
				'title'           => isset( $_POST['title'] ) ? wp_unslash( $_POST['title'] ) : '',
				'welcomeMessage'  => isset( $_POST['welcomeMessage'] ) ? wp_unslash( $_POST['welcomeMessage'] ) : '',
				'iconUrl'         => isset( $_POST['iconUrl'] ) ? wp_unslash( $_POST['iconUrl'] ) : '',
				'avatarUrl'       => isset( $_POST['avatarUrl'] ) ? wp_unslash( $_POST['avatarUrl'] ) : '',
			);

		$sanitized = $this->sanitize_design_settings( $raw_settings );

		update_option( 'aria_design_settings', $sanitized );
		update_option( 'aria_design_configured', true );

		wp_send_json_success(
			array(
				'message'  => __( 'Design settings saved successfully.', 'aria' ),
				'settings' => $sanitized,
			)
		);
	}

	/**
	 * Handle get general settings AJAX request.
	 */
	public function handle_get_general_settings() {
		if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		$settings = get_option( 'aria_general_settings', array() );
		$prepared = $this->sanitize_general_settings( $settings );

		wp_send_json_success(
			array(
				'settings' => $prepared,
			)
		);
	}

	/**
	 * Handle save general settings AJAX request.
	 */
	public function handle_save_general_settings() {
		if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		$raw_settings = array(
			'enableChat'    => isset( $_POST['enableChat'] ) ? wp_unslash( $_POST['enableChat'] ) : '',
			'displayOn'     => isset( $_POST['displayOn'] ) ? wp_unslash( $_POST['displayOn'] ) : '',
			'autoOpenDelay' => isset( $_POST['autoOpenDelay'] ) ? wp_unslash( $_POST['autoOpenDelay'] ) : '',
			'requireEmail'  => isset( $_POST['requireEmail'] ) ? wp_unslash( $_POST['requireEmail'] ) : '',
		);

		$sanitized = $this->sanitize_general_settings( $raw_settings );

		update_option( 'aria_general_settings', $sanitized );
		update_option( 'aria_chat_enabled', $sanitized['enableChat'] );

		wp_send_json_success(
			array(
				'message'  => __( 'General settings saved successfully.', 'aria' ),
				'settings' => $sanitized,
			)
		);
	}

	/**
	 * Get default general settings.
	 *
	 * @return array
	 */
	private function get_default_general_settings() {
		return array(
			'enableChat'    => true,
			'displayOn'     => 'all',
			'autoOpenDelay' => 0,
			'requireEmail'  => false,
		);
	}

	/**
	 * Get default design settings.
	 *
	 * @return array
	 */
	private function get_default_design_settings() {
		return array(
			'position'        => 'bottom-right',
			'size'            => 'medium',
			'theme'           => 'light',
			'primaryColor'    => '#2271b1',
			'backgroundColor' => '#ffffff',
			'textColor'       => '#1e1e1e',
			'title'           => __( 'Chat with us', 'aria' ),
			'welcomeMessage'  => __( 'Hi! How can I help you today?', 'aria' ),
			'iconUrl'         => '',
			'avatarUrl'       => '',
		);
	}

	/**
	 * Default notification settings.
	 *
	 * @return array
	 */
	private function get_default_notification_settings() {
		return array(
			'enableNotifications'  => false,
			'additionalRecipients' => '',
			'newConversation'      => true,
		);
	}

	/**
	 * Default privacy settings.
	 *
	 * @return array
	 */
	private function get_default_privacy_settings() {
		return array(
			'enableGDPR'        => false,
			'privacyPolicyUrl' => '',
			'dataRetention'    => 90,
		);
	}

	/**
	 * Sanitize design settings array.
	 *
	 * @param array $settings Raw settings.
	 * @return array Sanitized settings.
	 */
		private function sanitize_design_settings( $settings ) {
			$defaults = $this->get_default_design_settings();
			$allowed_positions = array( 'bottom-right', 'bottom-left', 'top-right', 'top-left' );
			$allowed_sizes     = array( 'small', 'medium', 'large' );
			$allowed_themes    = array( 'light', 'dark', 'auto' );

		$position = sanitize_text_field( $settings['position'] ?? $defaults['position'] );
		if ( ! in_array( $position, $allowed_positions, true ) ) {
			$position = $defaults['position'];
		}

		$size = sanitize_text_field( $settings['size'] ?? $defaults['size'] );
		if ( ! in_array( $size, $allowed_sizes, true ) ) {
			$size = $defaults['size'];
		}

		$theme = sanitize_text_field( $settings['theme'] ?? $defaults['theme'] );
		if ( ! in_array( $theme, $allowed_themes, true ) ) {
			$theme = $defaults['theme'];
		}

		$primary = sanitize_hex_color( $settings['primaryColor'] ?? '' );
		$background = sanitize_hex_color( $settings['backgroundColor'] ?? '' );
		$text = sanitize_hex_color( $settings['textColor'] ?? '' );

			$icon_url   = $this->sanitize_design_asset_url( $settings['iconUrl'] ?? $defaults['iconUrl'] );
			$avatar_url = $this->sanitize_design_asset_url( $settings['avatarUrl'] ?? $defaults['avatarUrl'] );

			return array(
				'position'        => $position,
				'size'            => $size,
				'theme'           => $theme,
				'primaryColor'    => $primary ? $primary : $defaults['primaryColor'],
				'backgroundColor' => $background ? $background : $defaults['backgroundColor'],
				'textColor'       => $text ? $text : $defaults['textColor'],
				'title'           => sanitize_text_field( $settings['title'] ?? $defaults['title'] ),
				'welcomeMessage'  => sanitize_text_field( $settings['welcomeMessage'] ?? $defaults['welcomeMessage'] ),
				'iconUrl'         => $icon_url,
				'avatarUrl'       => $avatar_url,
			);
		}

		/**
		 * Sanitize a design asset URL, ensuring it references an allowed file type.
		 *
		 * @param string $url Raw URL.
		 * @return string Sanitized URL or empty string.
		 */
		private function sanitize_design_asset_url( $url ) {
			$url = esc_url_raw( $url );

			if ( empty( $url ) ) {
				return '';
			}

			$allowed_extensions = array( 'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp' );
			$filetype           = wp_check_filetype( $url );

			if ( empty( $filetype['ext'] ) ) {
				return '';
			}

			$ext = strtolower( $filetype['ext'] );

			if ( ! in_array( $ext, $allowed_extensions, true ) ) {
				return '';
			}

			return $url;
		}

	/**
	 * Sanitize general settings.
	 *
	 * @param array $settings Raw settings.
	 * @return array
	 */
	private function sanitize_general_settings( $settings ) {
		$defaults = $this->get_default_general_settings();
		$allowed_display = array( 'all', 'home', 'posts', 'pages' );

		$enable_chat = isset( $settings['enableChat'] )
			? filter_var( $settings['enableChat'], FILTER_VALIDATE_BOOLEAN )
			: $defaults['enableChat'];

		$display = sanitize_text_field( $settings['displayOn'] ?? $defaults['displayOn'] );
		if ( ! in_array( $display, $allowed_display, true ) ) {
			$display = $defaults['displayOn'];
		}

		$delay = isset( $settings['autoOpenDelay'] ) ? intval( $settings['autoOpenDelay'] ) : $defaults['autoOpenDelay'];
		if ( $delay < 0 ) {
			$delay = 0;
		}
		if ( $delay > 120 ) {
			$delay = 120;
		}

		$require_email = isset( $settings['requireEmail'] )
			? filter_var( $settings['requireEmail'], FILTER_VALIDATE_BOOLEAN )
			: $defaults['requireEmail'];

		return array(
			'enableChat'    => $enable_chat,
			'displayOn'     => $display,
			'autoOpenDelay' => $delay,
			'requireEmail'  => $require_email,
		);
	}

	/**
	 * Sanitize notification settings.
	 *
	 * @param array $settings Raw settings.
	 * @return array
	 */
	private function sanitize_notification_settings( $settings ) {
		$defaults = $this->get_default_notification_settings();
		
		$enable = isset( $settings['enableNotifications'] )
			? filter_var( $settings['enableNotifications'], FILTER_VALIDATE_BOOLEAN )
			: $defaults['enableNotifications'];

		$new_conversation = isset( $settings['newConversation'] )
			? filter_var( $settings['newConversation'], FILTER_VALIDATE_BOOLEAN )
			: $defaults['newConversation'];

		$raw_recipients = isset( $settings['additionalRecipients'] )
			? explode( ',', $settings['additionalRecipients'] )
			: array();

		$clean_recipients = array();
		foreach ( $raw_recipients as $recipient ) {
			$recipient = sanitize_email( trim( $recipient ) );
			if ( ! empty( $recipient ) && is_email( $recipient ) ) {
				$clean_recipients[] = $recipient;
			}
		}

		return array(
			'enableNotifications'  => $enable,
			'additionalRecipients' => implode( ', ', $clean_recipients ),
			'newConversation'      => $new_conversation,
		);
	}

	/**
	 * Sanitize privacy settings.
	 *
	 * @param array $settings Raw settings.
	 * @return array
	 */
	private function sanitize_privacy_settings( $settings ) {
		$defaults = $this->get_default_privacy_settings();

		$enable_gdpr = isset( $settings['enableGDPR'] )
			? filter_var( $settings['enableGDPR'], FILTER_VALIDATE_BOOLEAN )
			: $defaults['enableGDPR'];

		$privacy_url = isset( $settings['privacyPolicyUrl'] )
			? esc_url_raw( $settings['privacyPolicyUrl'] )
			: $defaults['privacyPolicyUrl'];

		$data_retention = isset( $settings['dataRetention'] )
			? max( 1, absint( $settings['dataRetention'] ) )
			: $defaults['dataRetention'];

		return array(
			'enableGDPR'        => $enable_gdpr,
			'privacyPolicyUrl' => $privacy_url,
			'dataRetention'    => $data_retention,
		);
	}

	/**
	 * Default license settings.
	 *
	 * @return array
	 */
	private function get_default_license_settings() {
		return array(
			'licenseKey'    => '',
			'licenseStatus' => 'inactive',
			'activatedAt'   => '',
		);
	}

	/**
	 * Sanitize license settings.
	 *
	 * @param array $settings Raw settings.
	 * @return array
	 */
	private function sanitize_license_settings( $settings ) {
		$defaults = $this->get_default_license_settings();

		$license_key = sanitize_text_field( $settings['licenseKey'] ?? $defaults['licenseKey'] );
		$license_status = sanitize_text_field( $settings['licenseStatus'] ?? $defaults['licenseStatus'] );
		$activated_at = sanitize_text_field( $settings['activatedAt'] ?? $defaults['activatedAt'] );

		if ( ! $license_key ) {
			$license_status = 'inactive';
			$activated_at   = '';
		}

		return array(
			'licenseKey'    => $license_key,
			'licenseStatus' => $license_status ?: 'inactive',
			'activatedAt'   => $activated_at,
		);
	}

	/**
	 * Prepare knowledge entry for JSON response.
	 *
	 * @param array $entry Entry data.
	 * @return array
	 */
	private function prepare_knowledge_entry_for_response( $entry ) {
		if ( empty( $entry ) ) {
			return array();
		}

		$tags_string = isset( $entry['tags'] ) ? (string) $entry['tags'] : '';
		$tag_items   = array_filter( array_map( 'trim', explode( ',', $tags_string ) ) );
		$tags_array  = array_map( 'sanitize_text_field', $tag_items );

		$content_plain = wp_strip_all_tags( (string) $entry['content'] );

		return array(
			'id'                   => isset( $entry['id'] ) ? intval( $entry['id'] ) : 0,
			'title'                => sanitize_text_field( $entry['title'] ?? '' ),
			'content'              => wp_kses_post( $entry['content'] ?? '' ),
			'content_preview'      => wp_trim_words( $content_plain, 35, '…' ),
			'context'              => wp_kses_post( $entry['context'] ?? '' ),
			'response_instructions'=> wp_kses_post( $entry['response_instructions'] ?? '' ),
			'category'             => sanitize_text_field( $entry['category'] ?? '' ),
			'tags'                 => implode( ', ', $tags_array ),
			'tags_array'           => $tags_array,
			'language'             => sanitize_text_field( $entry['language'] ?? 'en' ),
			'priority'             => isset( $entry['priority'] ) ? intval( $entry['priority'] ) : 0,
			'status'               => sanitize_text_field( $entry['status'] ?? 'pending_processing' ),
			'total_chunks'         => isset( $entry['total_chunks'] ) ? intval( $entry['total_chunks'] ) : 0,
			'created_at'           => $entry['created_at'] ?? '',
			'updated_at'           => $entry['updated_at'] ?? '',
		);
	}

	/**
	 * Handle test API AJAX request.
	 */
	public function handle_test_api() {
		// Verify nonce
		if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'aria' ) ) );
		}

		$provider = isset( $_POST['provider'] ) ? sanitize_text_field( $_POST['provider'] ) : '';
		$api_key  = isset( $_POST['api_key'] ) ? sanitize_text_field( $_POST['api_key'] ) : '';

		if ( empty( $provider ) || empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Provider and API key are required.', 'aria' ) ) );
		}

		// Test the API connection
		try {
			$ai_provider = $this->create_ai_provider( $provider, $api_key );
			$result      = $ai_provider->test_connection();

			if ( $result ) {
				wp_send_json_success( array( 'message' => __( 'API connection successful!', 'aria' ) ) );
			} else {
				wp_send_json_error( array( 'message' => __( 'API connection failed. Please check your credentials.', 'aria' ) ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Handle test saved API AJAX request.
	 */
	public function handle_test_saved_api() {
		// Verify nonce
		if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'aria' ) ) );
		}

		$provider = isset( $_POST['provider'] ) ? sanitize_text_field( $_POST['provider'] ) : get_option( 'aria_ai_provider', 'openai' );
		
		// Get saved API key
		$encrypted_key = get_option( 'aria_ai_api_key' );
		if ( empty( $encrypted_key ) ) {
			wp_send_json_error( array( 'message' => __( 'No API key found. Please save an API key first.', 'aria' ) ) );
		}

		// Decrypt the key
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-security.php';
		$api_key = Aria_Security::decrypt( $encrypted_key );
		
		// Debug logging
		Aria_Logger::debug( 'Aria Test Saved API - Provider: ' . $provider );
		Aria_Logger::debug( 'Aria Test Saved API - Encrypted key exists: ' . ( $encrypted_key ? 'yes' : 'no' ) );
		Aria_Logger::debug( 'Aria Test Saved API - Decrypted key exists: ' . ( $api_key ? 'yes' : 'no' ) );
		Aria_Logger::debug( 'Aria Test Saved API - Decrypted key length: ' . strlen( $api_key ) );
		
		if ( empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Failed to decrypt API key. Please re-enter your API key.', 'aria' ) ) );
		}

		// Test the API connection
		try {
			Aria_Logger::debug( 'Aria Test Saved API - About to create provider' );
			$ai_provider = $this->create_ai_provider( $provider, $api_key );
			Aria_Logger::debug( 'Aria Test Saved API - Provider created, testing connection' );
			$result      = $ai_provider->test_connection();
			Aria_Logger::debug( 'Aria Test Saved API - Test result: ' . ( $result ? 'success' : 'failed' ) );

			if ( $result ) {
				wp_send_json_success( array( 'message' => __( 'API connection successful!', 'aria' ) ) );
			} else {
				Aria_Logger::error( 'Aria Test Saved API - Connection test returned false' );
				wp_send_json_error( array( 'message' => __( 'API connection failed. Please check your credentials.', 'aria' ) ) );
			}
		} catch ( Exception $e ) {
			Aria_Logger::error( 'Aria Test Saved API - Exception: ' . $e->getMessage() );
			Aria_Logger::error( 'Aria Test Saved API - Exception trace: ' . $e->getTraceAsString() );
			wp_send_json_error( array( 'message' => 'Error: ' . $e->getMessage() ) );
		}
	}


	/**
	 * Handle track event AJAX request.
	 */
	public function handle_track_event() {
		// Verify nonce
		if ( ! check_ajax_referer( 'aria_public_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		$event           = isset( $_POST['event'] ) ? sanitize_text_field( $_POST['event'] ) : '';
		$conversation_id = isset( $_POST['conversation_id'] ) ? intval( $_POST['conversation_id'] ) : 0;

		// For now, just log the event
		if ( $event ) {
			do_action( 'aria_track_event', $event, $conversation_id );
		}

		wp_send_json_success();
	}

	/**
	 * Handle submit feedback AJAX request.
	 */
	public function handle_submit_feedback() {
		// Verify nonce
		if ( ! check_ajax_referer( 'aria_public_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		$conversation_id = isset( $_POST['conversation_id'] ) ? intval( $_POST['conversation_id'] ) : 0;
		$rating          = isset( $_POST['rating'] ) ? sanitize_text_field( $_POST['rating'] ) : '';

		if ( ! $conversation_id || ! in_array( $rating, array( 'positive', 'negative' ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid feedback data.', 'aria' ) ) );
		}

		// Update learning data with feedback
		global $wpdb;
		$table = $wpdb->prefix . 'aria_learning_data';

		$wpdb->update(
			$table,
			array( 'feedback_rating' => $rating ),
			array( 'conversation_id' => $conversation_id ),
			array( '%s' ),
			array( '%d' )
		);

		wp_send_json_success( array( 'message' => __( 'Thank you for your feedback!', 'aria' ) ) );
	}

	/**
	 * Get or create conversation.
	 *
	 * @param string $session_id Session ID.
	 * @param string $name Guest name.
	 * @param string $email Guest email.
	 * @param string $phone Guest phone.
	 * @param string $initial_question Initial question.
	 * @return int Conversation ID.
	 */
	private function get_or_create_conversation( $session_id, $name, $email, $phone, $initial_question ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_conversations';

		// Try to get existing conversation
		$conversation = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM $table WHERE session_id = %s AND site_id = %d AND status = 'active' ORDER BY created_at DESC LIMIT 1",
				$session_id,
				get_current_blog_id()
			)
		);

		if ( $conversation ) {
			// Update name, email, and phone if provided
			if ( ! empty( $name ) || ! empty( $email ) || ! empty( $phone ) ) {
				$wpdb->update(
					$table,
					array(
						'guest_name'  => $name,
						'guest_email' => $email,
						'guest_phone' => $phone,
					),
					array( 'id' => $conversation->id )
				);
			}
			return $conversation->id;
		}

		// Create new conversation
		$wpdb->insert(
			$table,
			array(
				'session_id'        => $session_id,
				'guest_name'        => $name,
				'guest_email'       => $email,
				'guest_phone'       => $phone,
				'initial_question'  => $initial_question,
				'conversation_log'  => json_encode( array() ),
				'status'            => 'active',
				'site_id'           => get_current_blog_id(),
				'gdpr_consent'      => 1, // Assuming consent was given
			)
		);

		$conversation_id = $wpdb->insert_id;
		
		// Send new conversation notification
		if ( $conversation_id && ! empty( $name ) && ! empty( $email ) && ! empty( $initial_question ) ) {
			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-email-handler.php';
			$email_handler = new Aria_Email_Handler();
			$email_handler->send_new_conversation_notification( $conversation_id, $name, $email, $initial_question );
		}

		return $conversation_id;
	}

	/**
	 * Get AI provider instance.
	 *
	 * @return object|false AI provider instance or false.
	 */
	private function get_ai_provider() {
		$provider = get_option( 'aria_ai_provider', 'openai' );
		$encrypted_key  = get_option( 'aria_ai_api_key' );

		if ( empty( $encrypted_key ) ) {
			return false;
		}

		// Decrypt the API key
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-security.php';
		$api_key = Aria_Security::decrypt( $encrypted_key );

		return $this->create_ai_provider( $provider, $api_key );
	}

	/**
	 * Create AI provider instance.
	 *
	 * @param string $provider Provider name.
	 * @param string $api_key API key.
	 * @return object AI provider instance.
	 */
	private function create_ai_provider( $provider, $api_key ) {
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-ai-provider.php';
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-personality.php';

		switch ( $provider ) {
			case 'openai':
				require_once ARIA_PLUGIN_PATH . 'includes/providers/class-aria-openai-provider.php';
				return new Aria_OpenAI_Provider( $api_key );

			case 'gemini':
				require_once ARIA_PLUGIN_PATH . 'includes/providers/class-aria-gemini-provider.php';
				return new Aria_Gemini_Provider( $api_key );

			default:
				throw new Exception( __( 'Invalid AI provider.', 'aria' ) );
		}
	}

	/**
	 * Get relevant knowledge for a question.
	 *
	 * @param string $question User question.
	 * @return string Relevant knowledge.
	 */
	private function get_relevant_knowledge( $question ) {
		// Try vector search first if enabled
		if ( get_option( 'aria_vector_enabled', true ) ) {
			$vector_knowledge = $this->get_vector_knowledge( $question );
			if ( ! empty( $vector_knowledge ) ) {
				return $vector_knowledge;
			}
		}
		
		// Fallback to legacy keyword search
		return $this->get_legacy_knowledge( $question );
	}

	/**
	 * Get knowledge using vector search system.
	 *
	 * @param string $question User question.
	 * @return string Vector search results.
	 */
	private function get_vector_knowledge( $question ) {
		$all_knowledge = array();

		try {
			// 1. Search WordPress content vectors
			$content_vectorizer = new Aria_Content_Vectorizer();
			$content_results = $content_vectorizer->search_similar_content( $question, 3, 0.3 );
			
			if ( ! empty( $content_results ) ) {
				$content_knowledge = array();
				foreach ( $content_results as $result ) {
					$metadata = json_decode( $result['metadata'], true );
					$content_knowledge[] = sprintf(
						"Content from %s (%s):\n%s\nURL: %s\n",
						$metadata['title'] ?? 'Untitled',
						$result['content_type'],
						$result['content_text'],
						$metadata['url'] ?? ''
					);
				}
				$all_knowledge[] = "WordPress Content:\n" . implode( "\n---\n", $content_knowledge );
				Aria_Logger::debug( 'Aria: Found ' . count( $content_results ) . ' WordPress content matches' );
			}

			// 2. Search knowledge base vectors (existing system)
			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-query-handler.php';
			
			$query_handler = new Aria_Query_Handler();
			
			// Get conversation context for enhanced search
			$conversation_context = $this->get_conversation_context( $this->conversationId );
			$context_array = array();
			
			if ( ! empty( $conversation_context ) ) {
				// Parse conversation context into array format
				$lines = explode( "\n", $conversation_context );
				foreach ( $lines as $line ) {
					if ( strpos( $line, ':' ) !== false ) {
						list( $role, $content ) = explode( ':', $line, 2 );
						$context_array[] = array(
							'role'    => trim( strtolower( $role ) ),
							'content' => trim( $content )
						);
					}
				}
			}
			
			// Get relevant context using multi-stage retrieval
			$kb_knowledge = $query_handler->find_relevant_context( $question, $context_array );
			
			if ( ! empty( $kb_knowledge ) ) {
				$all_knowledge[] = "Knowledge Base:\n" . $kb_knowledge;
				Aria_Logger::debug( 'Aria: Found knowledge base matches' );
			}
			
			// Combine all knowledge sources
			if ( ! empty( $all_knowledge ) ) {
				$combined_knowledge = implode( "\n\n=================\n\n", $all_knowledge );
				Aria_Logger::debug( 'Aria: Using combined vector search results for question: ' . substr( $question, 0, 50 ) );
				return $combined_knowledge;
			}
			
		} catch ( Exception $e ) {
			Aria_Logger::error( 'Aria Vector Search Error: ' . $e->getMessage() );
		}
		
		return '';
	}

	/**
	 * Get knowledge using legacy keyword search.
	 *
	 * @param string $question User question.
	 * @return string Legacy search results.
	 */
	private function get_legacy_knowledge( $question ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_knowledge_base';

		// Debug logging
		Aria_Logger::debug( 'Aria Knowledge Search - Question: ' . $question );
		Aria_Logger::debug( 'Aria Knowledge Search - Current blog ID: ' . get_current_blog_id() );

		// First, let's check both with and without site_id filter
		$test_query = "SELECT COUNT(*) FROM $table WHERE site_id = %d";
		$count_with_site = $wpdb->get_var( $wpdb->prepare( $test_query, get_current_blog_id() ) );
		
		$test_query_all = "SELECT COUNT(*) FROM $table";
		$count_all = $wpdb->get_var( $test_query_all );
		
		Aria_Logger::debug( 'Aria Knowledge Search - Entries with site_id ' . get_current_blog_id() . ': ' . $count_with_site );
		Aria_Logger::debug( 'Aria Knowledge Search - Total entries in table: ' . $count_all );

		if ( $count_all == 0 ) {
			Aria_Logger::debug( 'Aria Knowledge Search - WARNING: No knowledge base entries found in table!' );
			return '';
		}
		
		// If no entries for current site but table has data, try without site_id filter
		$use_site_filter = ( $count_with_site > 0 );

		// Simple keyword search that should definitely work
		$keywords = explode( ' ', strtolower( $question ) );
		$where    = array();

		// Add common employment-related synonyms
		$employment_keywords = array( 'job', 'work', 'career', 'employment', 'hiring', 'position', 'opening' );
		$has_employment_query = false;
		
		foreach ( $keywords as $keyword ) {
			if ( in_array( $keyword, $employment_keywords ) ) {
				$has_employment_query = true;
				// Add all employment synonyms to search
				foreach ( $employment_keywords as $emp_keyword ) {
					$where[] = $wpdb->prepare( 
						'(title LIKE %s OR content LIKE %s OR tags LIKE %s)', 
						'%' . $wpdb->esc_like( $emp_keyword ) . '%', 
						'%' . $wpdb->esc_like( $emp_keyword ) . '%',
						'%' . $wpdb->esc_like( $emp_keyword ) . '%'
					);
				}
				break;
			}
		}

		// Regular keyword search for other terms
		if ( ! $has_employment_query ) {
			foreach ( $keywords as $keyword ) {
				if ( strlen( $keyword ) > 2 ) {
					$where[] = $wpdb->prepare( 
						'(title LIKE %s OR content LIKE %s OR context LIKE %s OR response_instructions LIKE %s OR tags LIKE %s)', 
						'%' . $wpdb->esc_like( $keyword ) . '%', 
						'%' . $wpdb->esc_like( $keyword ) . '%',
						'%' . $wpdb->esc_like( $keyword ) . '%',
						'%' . $wpdb->esc_like( $keyword ) . '%',
						'%' . $wpdb->esc_like( $keyword ) . '%'
					);
				}
			}
		}

		if ( empty( $where ) ) {
			Aria_Logger::debug( 'Aria Knowledge Search - No valid search terms' );
			// If no valid search terms, at least search for the full question
			$where[] = $wpdb->prepare( 
				'(content LIKE %s)', 
				'%' . $wpdb->esc_like( $question ) . '%'
			);
		}

		// Build query based on whether we need site filter
		if ( $use_site_filter ) {
			$sql = "SELECT title, content, context, response_instructions FROM $table WHERE site_id = %d AND (" . implode( ' OR ', $where ) . ') LIMIT 5';
			$params = array( get_current_blog_id() );
		} else {
			// Try without site_id filter if no entries for current site
			$sql = "SELECT title, content, context, response_instructions FROM $table WHERE " . implode( ' OR ', $where ) . ' LIMIT 5';
			$params = array();
		}
		
		// Debug the final query
		if ( $use_site_filter ) {
			$final_query = $wpdb->prepare( $sql, $params );
		} else {
			$final_query = $sql;
		}
		Aria_Logger::debug( 'Aria Knowledge Search - SQL Query: ' . $final_query );

		$results = $wpdb->get_results( $final_query, ARRAY_A );

		if ( empty( $results ) ) {
			Aria_Logger::debug( 'Aria Knowledge Search - No results found with search criteria, fetching all entries' );
			// If no specific matches, get ALL knowledge base entries
			if ( $use_site_filter ) {
				$fallback_sql = "SELECT title, content, context, response_instructions FROM $table WHERE site_id = %d LIMIT 10";
				$results = $wpdb->get_results(
					$wpdb->prepare( $fallback_sql, get_current_blog_id() ),
					ARRAY_A
				);
			} else {
				$fallback_sql = "SELECT title, content, context, response_instructions FROM $table LIMIT 10";
				$results = $wpdb->get_results( $fallback_sql, ARRAY_A );
			}
			
			if ( empty( $results ) ) {
				Aria_Logger::debug( 'Aria Knowledge Search - No knowledge base entries at all!' );
				return '';
			}
		}

		Aria_Logger::debug( 'Aria Knowledge Search - Found ' . count( $results ) . ' results' );
		$knowledge = "=== RELEVANT KNOWLEDGE FROM DATABASE ===\n\n";
		
		// If searching for employment/careers, prioritize that content
		$is_employment_query = false;
		$employment_terms = array( 'job', 'work', 'career', 'employment', 'hiring', 'position', 'opening', 'apply' );
		foreach ( $employment_terms as $term ) {
			if ( stripos( $question, $term ) !== false ) {
				$is_employment_query = true;
				break;
			}
		}

		foreach ( $results as $index => $result ) {
			$knowledge .= "--- Knowledge Entry " . ($index + 1) . " ---\n";
			$knowledge .= "Title: " . $result['title'] . "\n";
			
			// Include context if available
			if ( ! empty( $result['context'] ) ) {
				$knowledge .= "When to use: " . $result['context'] . "\n";
			}
			
			// For long content, try to extract relevant sections
			$content = $result['content'];
			if ( strlen( $content ) > 1000 && $is_employment_query ) {
				// Try to find career/employment section
				foreach ( $employment_terms as $term ) {
					if ( preg_match( '/(.{0,200}' . preg_quote( $term, '/' ) . '.{0,500})/i', $content, $matches ) ) {
						$knowledge .= "Relevant Section: " . trim( $matches[0] ) . "\n";
						break;
					}
				}
			} else {
				$knowledge .= "Information: " . $content . "\n";
			}
			
			// Include response instructions if available
			if ( ! empty( $result['response_instructions'] ) ) {
				$knowledge .= "How to respond: " . $result['response_instructions'] . "\n";
			}
			
			$knowledge .= "\n";
		}

		$knowledge .= "=== END OF KNOWLEDGE BASE ===\n";

		return $knowledge;
	}

	/**
	 * Get personality settings.
	 *
	 * @return array Personality settings.
	 */
	private function get_personality_settings() {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_personality_settings';

		$settings = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE site_id = %d",
				get_current_blog_id()
			),
			ARRAY_A
		);

		if ( ! $settings ) {
			return array(
				'business_type'      => 'general',
				'tone_setting'       => 'professional',
				'personality_traits' => 'helpful,knowledgeable,friendly',
				'greeting_message'   => __( 'Hello! I\'m Aria, your assistant. How can I help you today?', 'aria' ),
			);
		}

		return $settings;
	}

	/**
	 * Build AI prompt.
	 *
	 * @param string $message User message.
	 * @param string $knowledge Relevant knowledge.
	 * @param array  $personality Personality settings.
	 * @return string AI prompt.
	 */
	private function build_prompt( $message, $knowledge, $personality ) {
		// Handle the business type properly
		$business_name = ( $personality['business_type'] === 'general' ) ? 'this business' : $personality['business_type'];
		
		// Start with a very clear role definition
		$prompt = "You are Aria, a customer service assistant for " . $business_name . ".\n\n";
		
		// Immediate context
		$prompt .= "SITUATION: A customer named " . ( ! empty( $_POST['name'] ) ? $_POST['name'] : 'a visitor' ) . " has asked you: \"" . $message . "\"\n\n";
		
		// Simple, clear instructions
		$prompt .= "YOUR JOB:\n";
		$prompt .= "1. Answer their question directly\n";
		$prompt .= "2. Use we/us/our when referring to the company\n";
		$prompt .= "3. Be " . $personality['tone_setting'] . " and " . str_replace( ',', ', ', $personality['personality_traits'] ) . "\n\n";
		
		// Knowledge base section
		if ( ! empty( $knowledge ) ) {
			$prompt .= "INFORMATION AVAILABLE TO YOU:\n";
			$prompt .= $knowledge . "\n\n";
			$prompt .= "INSTRUCTIONS:\n";
			$prompt .= "1. Review each knowledge entry above\n";
			$prompt .= "2. Pay attention to the 'When to use' context to ensure relevance\n";
			$prompt .= "3. Use the 'Information' section for facts and details\n";
			$prompt .= "4. Follow any 'How to respond' instructions for tone and approach\n";
			$prompt .= "5. If multiple entries apply, combine them appropriately\n";
		} else {
			$prompt .= "NOTE: No specific information was found in the knowledge base for this question.\n";
			$prompt .= "INSTRUCTIONS: Politely explain that you don't have that information but will forward their question to the team.\n";
		}
		
		$prompt .= "\nIMPORTANT RULES:\n";
		$prompt .= "- NEVER ask for knowledge base documents - you already have what you need\n";
		$prompt .= "- NEVER say you're waiting for information\n";
		$prompt .= "- ALWAYS give a direct response to their question\n";
		$prompt .= "- Follow any response instructions provided in the knowledge entries\n";
		$prompt .= "- If you don't know, say: \"I don't have that specific information, but I'll make sure to forward your question to our team.\"\n";
		
		$prompt .= "\nNow respond to their question:";

		return $prompt;
	}

	/**
	 * Get conversation context.
	 *
	 * @param int $conversation_id Conversation ID.
	 * @return string Conversation context.
	 */
	private function get_conversation_context( $conversation_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_conversations';

		$conversation = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT conversation_log FROM $table WHERE id = %d",
				$conversation_id
			)
		);

		if ( ! $conversation ) {
			return '';
		}

		$messages = json_decode( $conversation, true );
		if ( ! is_array( $messages ) ) {
			return '';
		}

		// Get last 5 messages for context
		$recent_messages = array_slice( $messages, -5 );
		$context         = '';

		foreach ( $recent_messages as $msg ) {
			$role    = isset( $msg['role'] ) ? $msg['role'] : ( isset( $msg['sender'] ) ? $msg['sender'] : '' );
			$role    = Aria_Database::normalize_conversation_role( $role );
			$content = isset( $msg['content'] ) ? wp_strip_all_tags( $msg['content'] ) : '';
			$content = trim( $content );
			if ( empty( $role ) || '' === $content ) {
				continue;
			}
			$prompt_role = ( 'aria' === $role ) ? 'assistant' : $role;
			$context    .= $prompt_role . ': ' . $content . "\n";
		}

		return $context;
	}

	/**
	 * Save message to conversation.
	 *
	 * @param int    $conversation_id Conversation ID.
	 * @param string $message Message content.
	 * @param string $full_content Full content for logging.
	 * @param string $role Message role (user/aria).
	 */
	private function save_to_conversation( $conversation_id, $message, $full_content, $role ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_conversations';

		// Get existing conversation log
		$conversation_log = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT conversation_log FROM $table WHERE id = %d",
				$conversation_id
			)
		);

		$messages = json_decode( $conversation_log, true );
		if ( ! is_array( $messages ) ) {
			$messages = array();
		}

		foreach ( $messages as &$existing_message ) {
			$existing_role                  = isset( $existing_message['role'] ) ? $existing_message['role'] : ( isset( $existing_message['sender'] ) ? $existing_message['sender'] : 'aria' );
			$normalized_existing_role       = Aria_Database::normalize_conversation_role( $existing_role );
			$existing_message['role']       = $normalized_existing_role;
			$existing_message['sender']     = $normalized_existing_role;
			$existing_message['timestamp']  = isset( $existing_message['timestamp'] ) ? $existing_message['timestamp'] : current_time( 'mysql' );
		}
		unset( $existing_message );

		$normalized_role = Aria_Database::normalize_conversation_role( $role );
		$sanitized_content = Aria_Security::sanitize_conversation_input( $message );

		if ( '' === $sanitized_content ) {
			return;
		}

		$messages[] = array(
			'role'      => $normalized_role,
			'sender'    => $normalized_role,
			'content'   => $sanitized_content,
			'timestamp' => current_time( 'mysql' ),
		);

		// Update conversation
		$wpdb->update(
			$table,
			array(
				'conversation_log' => wp_json_encode( $messages ),
				'updated_at'       => current_time( 'mysql' ),
			),
			array( 'id' => $conversation_id )
		);
	}

	/**
	 * Track learning data.
	 *
	 * @param int    $conversation_id Conversation ID.
	 * @param string $question User question.
	 * @param string $response AI response.
	 */
	private function track_learning_data( $conversation_id, $question, $response ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_learning_data';

		$wpdb->insert(
			$table,
			array(
				'conversation_id' => $conversation_id,
				'question'        => $question,
				'response'        => $response,
				'site_id'         => get_current_blog_id(),
			)
		);
	}
	
	/**
	 * Handle test notification AJAX request.
	 */
	public function handle_test_notification() {
		// Verify nonce
		$nonce_valid = check_ajax_referer( 'aria_test_notification', 'nonce', false );
		if ( ! $nonce_valid ) {
			$nonce_valid = check_ajax_referer( 'aria_admin_nonce', 'nonce', false );
		}

		if ( ! $nonce_valid ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}
		
		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'aria' ) ) );
		}
		
		// Get notification settings
		$notification_settings = get_option( 'aria_notification_settings', array() );
		
		if ( empty( $notification_settings['enabled'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Email notifications are disabled. Please enable them first.', 'aria' ) ) );
		}
		
		// Create test conversation data
		$test_data = array(
			'conversation_id' => 999999,
			'user_name'       => 'Test User',
			'user_email'      => 'test@example.com',
			'initial_message' => 'This is a test message to verify email notifications are working correctly.',
			'page_url'        => home_url(),
			'timestamp'       => current_time( 'mysql' ),
		);
		
		// Send test email
		$email_handler = new Aria_Email_Handler();
		$subject = sprintf(
			'[%s] Test Notification - Aria Chat',
			get_bloginfo( 'name' )
		);
		
		$message = $email_handler->format_new_conversation_email( $test_data );
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>',
		);
		
		$recipients = $email_handler->get_recipient_emails( $notification_settings );
		
		if ( empty( $recipients ) ) {
			wp_send_json_error( array( 'message' => __( 'No recipients configured. Please add email recipients.', 'aria' ) ) );
		}
		
		$sent = wp_mail( $recipients, $subject, $message, $headers );
		
		if ( $sent ) {
			wp_send_json_success( array( 
				'message' => sprintf( 
					__( 'Test email sent successfully to: %s', 'aria' ), 
					implode( ', ', $recipients ) 
				) 
			) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to send test email. Please check your WordPress email configuration.', 'aria' ) ) );
		}
	}

	/**
	 * Handle process knowledge entry AJAX request.
	 */
	public function handle_process_knowledge_entry() {
		// Verify nonce
		if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'aria' ) ) );
		}

		$entry_id = isset( $_POST['entry_id'] ) ? intval( $_POST['entry_id'] ) : 0;

		if ( ! $entry_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid entry ID.', 'aria' ) ) );
		}

		try {
			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-background-processor.php';
			
			$processor = Aria_Background_Processor::instance();
			$scheduled = $processor->schedule_embedding_generation( $entry_id );

			if ( $scheduled ) {
				wp_send_json_success( array( 'message' => __( 'Entry processing scheduled successfully.', 'aria' ) ) );
			} else {
				wp_send_json_error( array( 'message' => __( 'Failed to schedule entry processing.', 'aria' ) ) );
			}

		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Handle get vector stats AJAX request.
	 */
	public function handle_get_vector_stats() {
		// Verify nonce
		if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'aria' ) ) );
		}

		try {
			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-background-processor.php';
			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-cache-manager.php';
			
			$processor = Aria_Background_Processor::instance();
			$cache_manager = new Aria_Cache_Manager();
			
			$stats = array(
				'processing' => $processor->get_processing_stats(),
				'cache' => $cache_manager->get_cache_stats()
			);

			wp_send_json_success( $stats );

		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Handle test vector system AJAX request.
	 */
	public function handle_test_vector_system() {
		// Verify nonce
		if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'aria' ) ) );
		}

		try {
			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-vector-engine.php';
			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-knowledge-processor.php';
			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-query-handler.php';
			
			$test_results = array();
			
			// Test vector engine
			$vector_engine = new Aria_Vector_Engine();
			$test_results['vector_engine'] = $vector_engine->test_vector_engine();
			
			// Test knowledge processor
			$knowledge_processor = new Aria_Knowledge_Processor();
			$test_results['knowledge_processor'] = $knowledge_processor->test_knowledge_processor();
			
			// Test query handler
			$query_handler = new Aria_Query_Handler();
			$test_results['query_handler'] = $query_handler->test_query_handler();

			wp_send_json_success( array( 
				'message' => __( 'Vector system test completed.', 'aria' ),
				'results' => $test_results
			) );

		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Handle retry failed processing AJAX request.
	 */
	public function handle_retry_failed_processing() {
		// Verify nonce
		if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'aria' ) ) );
		}

		try {
			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-background-processor.php';
			
			$processor = Aria_Background_Processor::instance();
			$entry_ids = isset( $_POST['entry_ids'] ) ? array_map( 'intval', $_POST['entry_ids'] ) : array();
			
			$scheduled_count = $processor->retry_failed_entries( $entry_ids );

			wp_send_json_success( array( 
				'message' => sprintf( 
					__( 'Scheduled %d entries for retry processing.', 'aria' ), 
					$scheduled_count 
				)
			) );

		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Handle AI knowledge generation AJAX request.
	 */
	public function handle_generate_knowledge_entry() {
		// Verify nonce (accept dedicated or general admin nonce for backward compatibility)
		$nonce_valid = check_ajax_referer( 'aria_generate_knowledge', 'nonce', false );
		if ( ! $nonce_valid ) {
			$nonce_valid = check_ajax_referer( 'aria_admin_nonce', 'nonce', false );
		}

		if ( ! $nonce_valid ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'aria' ) ) );
		}

		$raw_content = isset( $_POST['content'] ) ? sanitize_textarea_field( $_POST['content'] ) : '';

		if ( empty( $raw_content ) ) {
			wp_send_json_error( array( 'message' => __( 'Content is required.', 'aria' ) ) );
		}

		try {
			// Get AI provider
			$ai_provider = $this->get_ai_provider();
			if ( ! $ai_provider ) {
				wp_send_json_error( array( 'message' => __( 'AI service is not configured.', 'aria' ) ) );
			}

			// Create knowledge generation prompt
			$prompt = $this->build_knowledge_generation_prompt( $raw_content );

			// Generate the knowledge entry data
			$response = $ai_provider->generate_response( $prompt, '' );

			// Parse the AI response into structured data
			$generated_data = $this->parse_knowledge_generation_response( $response );

			if ( is_wp_error( $generated_data ) ) {
				wp_send_json_error( array( 'message' => $generated_data->get_error_message() ) );
			}

			if ( ! $generated_data ) {
				wp_send_json_error( array( 'message' => __( 'Failed to parse AI response. Please try again.', 'aria' ) ) );
			}

			wp_send_json_success( $generated_data );

		} catch ( Exception $e ) {
			$error_detail = wp_strip_all_tags( $e->getMessage() );
			Aria_Logger::error( 'Aria Knowledge Generation Error: ' . $error_detail );
			$message = $error_detail
				? sprintf( __( 'Failed to generate knowledge entry: %s', 'aria' ), $error_detail )
				: __( 'Failed to generate knowledge entry. Please try again.', 'aria' );

			wp_send_json_error( array( 'message' => $message ) );
		}
	}

	/**
	 * Build prompt for knowledge generation.
	 *
	 * @param string $raw_content Raw content to process.
	 * @return string Generation prompt.
	 */
	private function build_knowledge_generation_prompt( $raw_content ) {
		$business_name = get_bloginfo( 'name' );
		$business_description = get_bloginfo( 'description' );

		$prompt = "You are an AI assistant helping to structure knowledge base content for a customer service chatbot named Aria.\n\n";
		$prompt .= "BUSINESS CONTEXT:\n";
		$prompt .= "Business Name: {$business_name}\n";
		if ( $business_description ) {
			$prompt .= "Business Description: {$business_description}\n";
		}
		$prompt .= "\n";

		$prompt .= "TASK: Analyze the following content and generate structured knowledge base fields.\n\n";
		$prompt .= "RAW CONTENT:\n";
		$prompt .= $raw_content . "\n\n";

		$prompt .= "Generate the following fields in EXACTLY this JSON format:\n";
		$prompt .= "{\n";
		$prompt .= '  "title": "Clear, descriptive title (max 100 characters)",' . "\n";
		$prompt .= '  "context": "When should Aria use this information? Describe situations, questions, or topics (2-3 sentences)",' . "\n";
		$prompt .= '  "content": "Clean, well-formatted version of the main information",' . "\n";
		$prompt .= '  "response_instructions": "How should Aria communicate this? Include tone and special instructions (2-3 sentences)",' . "\n";
		$prompt .= '  "category": "Single category name (e.g., Products, Policies, Support, FAQs)",' . "\n";
		$prompt .= '  "tags": "Comma-separated keywords for better matching",' . "\n";
		$prompt .= '  "language": "en"' . "\n";
		$prompt .= "}\n\n";

		$prompt .= "GUIDELINES:\n";
		$prompt .= "- Title: Make it clear and searchable\n";
		$prompt .= "- Context: Focus on when customers would ask about this\n";
		$prompt .= "- Content: Keep factual information, improve formatting\n";
		$prompt .= "- Instructions: Consider tone (helpful, professional) and any special handling\n";
		$prompt .= "- Category: Choose appropriate business category\n";
		$prompt .= "- Tags: Include keywords customers might use\n";
		$prompt .= "- Language: Default to 'en' unless content is clearly in another language\n\n";

		$prompt .= "IMPORTANT: Respond ONLY with valid JSON. No additional text or explanations.";

		return $prompt;
	}

	/**
	 * Parse AI response for knowledge generation.
	 *
	 * @param string $response AI response.
	 * @return array|false Parsed data or false on failure.
	 */
	private function parse_knowledge_generation_response( $response ) {
		$response = trim( (string) $response );

		if ( '' === $response ) {
			return new WP_Error( 'aria_ai_empty', __( 'The AI response was empty. Please try again.', 'aria' ) );
		}

		$json_start = strpos( $response, '{' );
		$json_end   = strrpos( $response, '}' );

		if ( false === $json_start || false === $json_end || $json_end <= $json_start ) {
			Aria_Logger::error(
				'Aria Knowledge Generation: JSON payload not found in AI response.',
				array( 'response_snippet' => $this->truncate_for_log( $response ) )
			);

			$fallback_data = $this->parse_structured_knowledge_response( $response );
			if ( false !== $fallback_data ) {
				Aria_Logger::debug( 'Aria Knowledge Generation: Parsed structured fallback response.' );
				return $fallback_data;
			}

			return new WP_Error( 'aria_ai_no_json', __( 'The AI response did not contain JSON data. Please try again.', 'aria' ) );
		}

		$json_string = substr( $response, $json_start, $json_end - $json_start + 1 );
		$json_string = $this->normalize_ai_json_output( $json_string );

		$data = json_decode( $json_string, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			$error_detail = json_last_error_msg();
			Aria_Logger::error(
				'Aria Knowledge Generation: JSON decode error – ' . $error_detail,
				array( 'json_snippet' => $this->truncate_for_log( $json_string ) )
			);

			$fallback_data = $this->parse_structured_knowledge_response( $response );
			if ( false !== $fallback_data ) {
				Aria_Logger::debug( 'Aria Knowledge Generation: Parsed structured fallback response after JSON decode failure.' );
				return $fallback_data;
			}

			return new WP_Error(
				'aria_ai_json_error',
				sprintf( __( 'The AI response JSON was invalid (%s). Please try again.', 'aria' ), $error_detail )
			);
		}

		$required_fields = array( 'title', 'content' );
		foreach ( $required_fields as $field ) {
			if ( ! isset( $data[ $field ] ) || '' === trim( (string) $data[ $field ] ) ) {
				Aria_Logger::error(
					'Aria Knowledge Generation: Missing required field in AI response.',
					array(
						'missing_field' => $field,
						'json_data'     => $data,
					)
				);
				return new WP_Error(
					'aria_ai_missing_field',
					sprintf( __( 'The AI response is missing the required "%s" field.', 'aria' ), $field )
				);
			}
		}

		$tags = '';
		if ( isset( $data['tags'] ) ) {
			if ( is_array( $data['tags'] ) ) {
				$tags = implode( ', ', array_filter( array_map( 'trim', $data['tags'] ) ) );
			} else {
				$tags = (string) $data['tags'];
			}
			$tags = sanitize_text_field( $tags );
		}

		$sanitized_data = array(
			'title'                 => sanitize_text_field( substr( (string) $data['title'], 0, 200 ) ),
			'context'               => isset( $data['context'] ) ? sanitize_textarea_field( (string) $data['context'] ) : '',
			'content'               => isset( $data['content'] ) ? wp_kses_post( $data['content'] ) : '',
			'response_instructions' => isset( $data['response_instructions'] ) ? sanitize_textarea_field( (string) $data['response_instructions'] ) : '',
			'category'              => isset( $data['category'] ) ? sanitize_text_field( (string) $data['category'] ) : '',
			'tags'                  => $tags,
			'language'              => isset( $data['language'] ) ? sanitize_text_field( (string) $data['language'] ) : 'en',
		);

		return $sanitized_data;
	}

	/**
	 * Normalize JSON string returned by AI providers so it can be decoded reliably.
	 *
	 * @param string $json_string Raw JSON string from AI response.
	 * @return string Normalized JSON string.
	 */
	private function normalize_ai_json_output( $json_string ) {
		$json_string = trim( (string) $json_string );
		$json_string = html_entity_decode( $json_string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );

		$smart_quotes = array(
			"\xE2\x80\x9C" => '"',
			"\xE2\x80\x9D" => '"',
			"\xE2\x80\x98" => "'",
			"\xE2\x80\x99" => "'",
		);
		$json_string = strtr( $json_string, $smart_quotes );

		// Remove trailing commas before closing braces/brackets
		$json_string = preg_replace( '/,\s*([}\]])/m', '$1', $json_string );

		// Normalise line endings
		$json_string = str_replace( array( "\r\n", "\r" ), "\n", $json_string );

		// Ensure newline characters inside quoted strings are escaped
		$escaped_json_string = preg_replace_callback(
			'~"([^"\\]|\\.)*"~s',
			function ( $matches ) {
				return str_replace( "\n", '\\n', $matches[0] );
			},
			$json_string
		);

		if ( is_string( $escaped_json_string ) ) {
			$json_string = $escaped_json_string;
		}

		return $json_string;
	}

	/**
	 * Attempt to parse structured plain-text responses into knowledge entry fields.
	 *
	 * @param string $response Raw AI response.
	 * @return array|false Parsed data or false if parsing fails.
	 */
	private function parse_structured_knowledge_response( $response ) {
		$response = trim( (string) $response );
		if ( '' === $response ) {
			return false;
		}

		$normalized = str_replace( array( '**', '__', '`' ), '', $response );
		$normalized = preg_replace( '/#+\s*/', '', $normalized );

		$segments = preg_split( '/\r?\n\s*\r?\n/', $normalized );
		$lines = array();
		foreach ( $segments as $segment ) {
			$parts = preg_split( '/\r?\n/', trim( $segment ) );
			foreach ( $parts as $part ) {
				$lines[] = $part;
			}
		}
		if ( empty( $lines ) ) {
			return false;
		}

		$field_aliases = array(
			'title'                 => array( 'heading', 'name', 'subject' ),
			'context'               => array( 'context', 'use case', 'use cases', 'usage context', 'when to use', 'scenario', 'scenarios' ),
			'content'               => array( 'content', 'details', 'summary', 'description', 'answer', 'body', 'information', 'response' ),
			'response_instructions' => array( 'response instructions', 'instructions', 'tone', 'voice', 'style', 'response guidance', 'communication style', 'handling guidance' ),
			'category'              => array( 'category', 'categories', 'topic', 'section', 'group' ),
			'tags'                  => array( 'tags', 'keywords', 'key words' ),
			'language'              => array( 'language', 'locale' ),
		);

		$collected = array();
		$unassigned = array();
		$current_key = null;

		foreach ( $lines as $raw_line ) {
			$line = trim( $raw_line );
			if ( '' === $line ) {
				$current_key = $current_key ? $current_key : null;
				continue;
			}

			$line = preg_replace( '/^[\-•\*]+\s*/u', '', $line );
			$line = preg_replace( '/^\d+[\).]\s*/', '', $line );

			if ( false !== strpos( $line, ':' ) ) {
				list( $label, $value ) = array_pad( explode( ':', $line, 2 ), 2, '' );
				$matched_key = $this->match_structured_field_label( $label, $field_aliases );
				if ( $matched_key ) {
					$current_key = $matched_key;
					$collected[ $matched_key ] = array();
					$value = trim( $value );
					if ( '' !== $value ) {
						$collected[ $matched_key ][] = $value;
					}
					continue;
				}
			}

			if ( $current_key ) {
				$collected[ $current_key ][] = $line;
			} else {
				$unassigned[] = $line;
			}
		}

		$title = isset( $collected['title'] ) ? trim( implode( ' ', $collected['title'] ) ) : '';
		$content_text = isset( $collected['content'] ) ? trim( implode( "\n", $collected['content'] ) ) : '';

		if ( '' === $content_text && ! empty( $unassigned ) ) {
			$content_text = trim( implode( "\n", $unassigned ) );
		}

		if ( '' === $title || '' === $content_text ) {
			return false;
		}

		$context = isset( $collected['context'] ) ? trim( implode( ' ', $collected['context'] ) ) : '';
		$instructions = isset( $collected['response_instructions'] ) ? trim( implode( ' ', $collected['response_instructions'] ) ) : '';
		$category = isset( $collected['category'] ) ? trim( implode( ' ', $collected['category'] ) ) : '';
		$tags_raw = isset( $collected['tags'] ) ? trim( implode( ' ', $collected['tags'] ) ) : '';
		$language = isset( $collected['language'] ) ? trim( implode( ' ', $collected['language'] ) ) : 'en';

		$tags = '';
		if ( '' !== $tags_raw ) {
			$tags = implode( ', ', array_filter( array_map( 'trim', preg_split( '/[,;]+/', $tags_raw ) ) ) );
		}

		if ( '' !== $category && false !== strpos( $category, ',' ) ) {
			$parts = array_filter( array_map( 'trim', explode( ',', $category ) ) );
			if ( ! empty( $parts ) ) {
				$category = $parts[0];
			}
		}

		$sanitized_data = array(
			'title'                 => sanitize_text_field( substr( $title, 0, 200 ) ),
			'context'               => $context ? sanitize_textarea_field( $context ) : '',
			'content'               => wp_kses_post( wpautop( $content_text ) ),
			'response_instructions' => $instructions ? sanitize_textarea_field( $instructions ) : '',
			'category'              => $category ? sanitize_text_field( $category ) : '',
			'tags'                  => $tags ? sanitize_text_field( $tags ) : '',
			'language'              => $language ? sanitize_text_field( $language ) : 'en',
		);

		return $sanitized_data;
	}

	/**
	 * Match a structured field label against known aliases.
	 *
	 * @param string $label Raw label text before colon.
	 * @param array  $alias_map Alias definitions.
	 * @return string|null Matched field key.
	 */
	private function match_structured_field_label( $label, $alias_map ) {
		$normalized = strtolower( preg_replace( '/[^a-z0-9\s]/i', '', $label ) );
		$normalized = trim( preg_replace( '/\s+/', ' ', $normalized ) );

		foreach ( $alias_map as $field => $aliases ) {
			if ( $normalized === $field ) {
				return $field;
			}
			if ( in_array( $normalized, $aliases, true ) ) {
				return $field;
			}
		}

		return null;
	}

	/**
	 * Generate a short snippet for logging without overwhelming the error log.
	 *
	 * @param string $value  String to truncate.
	 * @param int    $length Maximum characters to keep.
	 * @return string Truncated string.
	 */
	private function truncate_for_log( $value, $length = 400 ) {
		$value = (string) $value;

		if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
			if ( mb_strlen( $value, 'UTF-8' ) > $length ) {
				return mb_substr( $value, 0, $length, 'UTF-8' ) . '...';
			}
			return $value;
		}

		if ( strlen( $value ) > $length ) {
			return substr( $value, 0, $length ) . '...';
		}

		return $value;
	}

	/**
	 * Handle vector system migration AJAX request.
	 */
	public function handle_migrate_vector_system() {
		// Verify nonce
		if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'aria' ) ) );
		}

		try {
			global $wpdb;

			// Force database migration
			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-db-updater.php';
			
			// Reset version to force migration
			delete_option( 'aria_db_version' );
			update_option( 'aria_db_version', '1.0.0' );
			
			// Run migration
			Aria_DB_Updater::update();

			// Check results
			$old_table = $wpdb->prefix . 'aria_knowledge_base';
			$new_table = $wpdb->prefix . 'aria_knowledge_entries';

			$old_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$old_table}" );
			$new_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$new_table}" );

			// Schedule processing for any pending entries
			$pending_entries = $wpdb->get_col( $wpdb->prepare(
				"SELECT id FROM {$new_table} WHERE status = %s",
				'pending_processing'
			) );

			if ( ! empty( $pending_entries ) ) {
				require_once ARIA_PLUGIN_PATH . 'includes/class-aria-background-processor.php';
				$processor = Aria_Background_Processor::instance();
				
				foreach ( $pending_entries as $entry_id ) {
					$processor->schedule_embedding_generation( $entry_id );
				}
			}

			wp_send_json_success( array(
				'message' => sprintf( 
					__( 'Migration completed! Migrated %d entries to vector system. Processing will begin shortly.', 'aria' ), 
					$new_count
				),
				'old_count' => $old_count,
				'new_count' => $new_count,
				'pending_count' => count( $pending_entries )
			) );

		} catch ( Exception $e ) {
			Aria_Logger::error( 'Aria Vector Migration Error: ' . $e->getMessage() );
			wp_send_json_error( array( 'message' => __( 'Migration failed. Please try again or check the error logs.', 'aria' ) ) );
		}
	}

	/**
	 * Debug vector system status (for troubleshooting).
	 */
	public function handle_debug_vector_system() {
		// Verify nonce and permissions
		if ( ! check_ajax_referer( 'aria_debug_vector', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		global $wpdb;
		$debug_info = array();

		// Check database tables
		$tables = array( 'aria_knowledge_entries', 'aria_knowledge_chunks', 'aria_search_cache' );
		foreach ( $tables as $table ) {
			$full_table = $wpdb->prefix . $table;
			$exists = $wpdb->get_var( "SHOW TABLES LIKE '{$full_table}'" );
			$count = $exists ? $wpdb->get_var( "SELECT COUNT(*) FROM {$full_table}" ) : 0;
			$debug_info['tables'][$table] = array( 'exists' => (bool) $exists, 'count' => $count );
		}

		// Check entry statuses
		$entries_table = $wpdb->prefix . 'aria_knowledge_entries';
		$status_counts = $wpdb->get_results( "SELECT status, COUNT(*) as count FROM {$entries_table} GROUP BY status", ARRAY_A );
		$debug_info['entry_statuses'] = $status_counts;

		// Check WordPress cron
		$debug_info['wp_cron'] = array(
			'enabled' => ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ),
			'aria_events' => 0
		);

		$cron_events = _get_cron_array();
		foreach ( $cron_events as $timestamp => $events ) {
			foreach ( $events as $hook => $event_data ) {
				if ( strpos( $hook, 'aria_' ) === 0 ) {
					$debug_info['wp_cron']['aria_events']++;
				}
			}
		}

		// Check AI configuration
		$debug_info['ai_config'] = array(
			'provider' => get_option( 'aria_ai_provider', 'openai' ),
			'api_key_set' => ! empty( get_option( 'aria_ai_api_key', '' ) )
		);

		// Test background processor
		try {
			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-background-processor.php';
			$processor = Aria_Background_Processor::instance();
			$debug_info['background_processor'] = array(
				'loaded' => true,
				'stats' => $processor->get_processing_stats()
			);
		} catch ( Exception $e ) {
			$debug_info['background_processor'] = array(
				'loaded' => false,
				'error' => $e->getMessage()
			);
		}

		wp_send_json_success( $debug_info );
	}

	/**
	 * Manually process one pending entry (for testing).
	 */
	public function handle_test_process_entry() {
		// Verify nonce and permissions
		if ( ! check_ajax_referer( 'aria_test_process', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		global $wpdb;
		$entries_table = $wpdb->prefix . 'aria_knowledge_entries';

		// Get one entry that needs processing (any status that indicates it needs processing)
		$pending_entry = $wpdb->get_row( $wpdb->prepare(
			"SELECT id, title, status FROM {$entries_table} WHERE status IN (%s, %s, %s) LIMIT 1",
			'pending_processing',
			'processing_scheduled',
			'failed'
		) );

		if ( ! $pending_entry ) {
			wp_send_json_error( array( 'message' => __( 'No entries found that need processing.', 'aria' ) ) );
		}

		try {
			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-background-processor.php';
			$processor = Aria_Background_Processor::instance();
			
			// Reset status to pending_processing before attempting to process
			$wpdb->update(
				$entries_table,
				array( 'status' => 'pending_processing' ),
				array( 'id' => $pending_entry->id ),
				array( '%s' ),
				array( '%d' )
			);
			
			$result = $processor->process_embeddings_async( $pending_entry->id );
			
			if ( false !== $result ) {
				wp_send_json_success( array( 
					'message' => sprintf( __( 'Successfully processed entry: %s (was %s)', 'aria' ), $pending_entry->title, $pending_entry->status ),
					'entry_id' => $pending_entry->id
				) );
			} else {
				wp_send_json_error( array( 'message' => __( 'Processing failed for unknown reason.', 'aria' ) ) );
			}
			
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Process all stuck entries (for fixing the queue).
	 */
	public function handle_process_all_stuck_entries() {
		// Verify nonce and permissions
		if ( ! check_ajax_referer( 'aria_process_all', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		global $wpdb;
		$entries_table = $wpdb->prefix . 'aria_knowledge_entries';

		// Get all stuck entries
		$stuck_entries = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, title, status FROM {$entries_table} WHERE status IN (%s, %s, %s) ORDER BY created_at ASC",
			'pending_processing',
			'processing_scheduled',
			'failed'
		) );

		if ( empty( $stuck_entries ) ) {
			wp_send_json_error( array( 'message' => __( 'No stuck entries found to process.', 'aria' ) ) );
		}

		try {
			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-background-processor.php';
			$processor = Aria_Background_Processor::instance();
			
			$processed_count = 0;
			$failed_count = 0;
			$results = array();

			foreach ( $stuck_entries as $entry ) {
				try {
					// Reset status to pending_processing
					$wpdb->update(
						$entries_table,
						array( 'status' => 'pending_processing' ),
						array( 'id' => $entry->id ),
						array( '%s' ),
						array( '%d' )
					);
					
					$result = $processor->process_embeddings_async( $entry->id );
					
					if ( false !== $result ) {
						$processed_count++;
						$results[] = "✅ {$entry->title}";
					} else {
						$failed_count++;
						$results[] = "❌ {$entry->title} (processing failed)";
					}
					
				} catch ( Exception $e ) {
					$failed_count++;
					$results[] = "❌ {$entry->title} (error: {$e->getMessage()})";
				}
			}

			wp_send_json_success( array( 
				'message' => sprintf( 
					__( 'Processing complete! %d processed, %d failed out of %d total entries.', 'aria' ), 
					$processed_count, 
					$failed_count, 
					count( $stuck_entries ) 
				),
				'processed_count' => $processed_count,
				'failed_count' => $failed_count,
				'total_count' => count( $stuck_entries ),
				'details' => $results
			) );
			
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Toggle immediate processing setting.
	 */
	public function handle_toggle_immediate_processing() {
		// Verify nonce and permissions
		if ( ! check_ajax_referer( 'aria_toggle_immediate', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		$enabled = isset( $_POST['enabled'] ) ? (bool) intval( $_POST['enabled'] ) : false;
		
		update_option( 'aria_immediate_processing', $enabled );
		
		$message = $enabled ? 
			__( 'Immediate processing enabled. New entries will be processed immediately.', 'aria' ) :
			__( 'Immediate processing disabled. Using WordPress cron scheduling.', 'aria' );

		wp_send_json_success( array( 'message' => $message, 'enabled' => $enabled ) );
	}

	/**
	 * Handle CSV export of conversations.
	 */
	public function handle_export_conversations_csv() {
		// Verify nonce and permissions
		if ( ! check_ajax_referer( 'aria_export_conversations', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		// Get all conversations for export
		$conversations = Aria_Database::get_conversations( array(
			'limit'  => 999999, // Export all conversations
			'offset' => 0,
		) );
		
		if ( empty( $conversations ) ) {
			wp_send_json_error( array( 'message' => __( 'No conversations found to export.', 'aria' ) ) );
		}

		// Generate filename
		$filename = 'aria-conversations-' . date( 'Y-m-d-H-i-s' ) . '.csv';
		
		// Clear any previous output
		if ( ob_get_level() ) {
			ob_end_clean();
		}
		
		// Set headers for CSV download
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		
		// Create CSV output
		$output = fopen( 'php://output', 'w' );
		
		// Add BOM for UTF-8
		fprintf( $output, chr(0xEF).chr(0xBB).chr(0xBF) );
		
		// Add CSV headers
		fputcsv( $output, array(
			'ID',
			'Guest Name',
			'Guest Email',
			'Guest Phone',
			'Status',
			'Created At',
			'Updated At',
			'Page Title',
			'Page URL',
			'Initial Question',
			'Total Messages',
			'Conversation Log'
		) );
		
		// Add conversation data
		foreach ( $conversations as $conversation ) {
			$messages = json_decode( $conversation['conversation_log'], true );
			$message_count = is_array( $messages ) ? count( $messages ) : 0;
			
			// Format conversation log for CSV (simple text format)
			$conversation_text = '';
			if ( is_array( $messages ) ) {
				foreach ( $messages as $message ) {
					$role   = isset( $message['role'] ) ? $message['role'] : ( isset( $message['sender'] ) ? $message['sender'] : 'aria' );
					$sender = ( 'user' === $role ) ? ( $conversation['guest_name'] ?: 'Visitor' ) : 'Aria';
					$conversation_text .= $sender . ': ' . strip_tags( $message['content'] ?? '' ) . "\n";
				}
			}
			
			fputcsv( $output, array(
				$conversation['id'],
				$conversation['guest_name'],
				$conversation['guest_email'],
				$conversation['guest_phone'],
				$conversation['status'],
				$conversation['created_at'],
				$conversation['updated_at'],
				isset( $conversation['page_title'] ) ? $conversation['page_title'] : '',
				isset( $conversation['page_url'] ) ? $conversation['page_url'] : '',
				$conversation['initial_question'],
				$message_count,
				$conversation_text
			) );
		}
		
			fclose( $output );
			exit;
		}

		/**
		 * Provide conversations data for the React admin experience.
		 */
		public function handle_get_conversations_data() {
			if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
			}

			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-database.php';

			$limit  = isset( $_POST['limit'] ) ? max( 1, min( 200, absint( wp_unslash( $_POST['limit'] ) ) ) ) : 50;
			$status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
			$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

			$args = array(
				'limit'   => $limit,
				'orderby' => 'created_at',
				'order'   => 'DESC',
			);

			if ( $status && 'all' !== $status ) {
				$args['status'] = $status;
			}

			if ( ! empty( $search ) ) {
				$args['search'] = $search;
			}

			$conversations = Aria_Database::get_conversations( $args );
			$prepared      = array();

			foreach ( $conversations as $conversation ) {
				$prepared[] = $this->prepare_admin_conversation_for_response( $conversation );
			}

			$metrics = $this->calculate_conversation_metrics();

			wp_send_json_success(
				array(
					'metrics'       => $metrics,
					'conversations' => $prepared,
				)
			);
		}

		/**
		 * Handle update conversation status AJAX request.
		 */
		public function handle_update_conversation_status() {
			// Check nonce and permissions
			if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
			}

			$conversation_id = isset( $_POST['conversation_id'] ) ? intval( $_POST['conversation_id'] ) : 0;
			$status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
			$allowed_statuses = array( 'active', 'resolved', 'pending', 'archived' );

			if ( ! $conversation_id || ! in_array( $status, $allowed_statuses, true ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid parameters.', 'aria' ) ) );
			}

			if ( Aria_Database::update_conversation( $conversation_id, array( 'status' => $status ) ) ) {
				wp_send_json_success( array(
					'message' => sprintf( __( 'Conversation marked as %s.', 'aria' ), ucfirst( $status ) ),
					'status'  => $status,
				) );
			} else {
				wp_send_json_error( array( 'message' => __( 'Failed to update conversation status.', 'aria' ) ) );
			}
		}
	
	/**
	 * Handle email transcript AJAX request.
	 */
	public function handle_email_transcript() {
		// Check nonce and permissions
		if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		$conversation_id = isset( $_POST['conversation_id'] ) ? intval( $_POST['conversation_id'] ) : 0;
		$email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';

		if ( ! $conversation_id || ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters.', 'aria' ) ) );
		}

		// Get conversation data
		$conversation = Aria_Database::get_conversation( $conversation_id );
		$messages = Aria_Database::get_conversation_messages( $conversation_id );

		if ( ! $conversation ) {
			wp_send_json_error( array( 'message' => __( 'Conversation not found.', 'aria' ) ) );
		}

		// Generate transcript
		$transcript = "Conversation Transcript\n";
		$transcript .= "===================\n\n";
		$transcript .= sprintf( "Visitor: %s\n", $conversation['guest_name'] ?: 'Anonymous' );
		if ( $conversation['guest_email'] ) {
			$transcript .= sprintf( "Email: %s\n", $conversation['guest_email'] );
		}
		$transcript .= sprintf( "Date: %s\n", wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $conversation['created_at'] ) ) );
		$transcript .= sprintf( "Status: %s\n\n", ucfirst( $conversation['status'] ) );

		$transcript .= "Messages:\n";
		$transcript .= "---------\n\n";

		foreach ( $messages as $message ) {
			$role = isset( $message['role'] ) ? $message['role'] : ( isset( $message['sender'] ) ? $message['sender'] : 'aria' );
			$sender = ( 'user' === $role ) ? ( $conversation['guest_name'] ?: 'Visitor' ) : 'Aria';
			$timestamp = wp_date( get_option( 'time_format' ), strtotime( $message['timestamp'] ) );
			$transcript .= sprintf( "[%s] %s: %s\n\n", $timestamp, $sender, wp_strip_all_tags( $message['content'] ?? '' ) );
		}

		// Send email
		$subject = sprintf( __( 'Conversation Transcript - %s', 'aria' ), get_bloginfo( 'name' ) );
		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

		if ( wp_mail( $email, $subject, $transcript, $headers ) ) {
			wp_send_json_success( array( 'message' => __( 'Transcript sent successfully.', 'aria' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to send transcript.', 'aria' ) ) );
		}
	}
	
	/**
	 * Handle add conversation note AJAX request.
	 */
	public function handle_add_conversation_note() {
		// Check nonce and permissions
		if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		$conversation_id = isset( $_POST['conversation_id'] ) ? intval( $_POST['conversation_id'] ) : 0;
		$note = isset( $_POST['note'] ) ? sanitize_textarea_field( $_POST['note'] ) : '';

		if ( ! $conversation_id || empty( $note ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters.', 'aria' ) ) );
		}

		// Get current conversation
		$conversation = Aria_Database::get_conversation( $conversation_id );
		if ( ! $conversation ) {
			wp_send_json_error( array( 'message' => __( 'Conversation not found.', 'aria' ) ) );
		}

		// Get current metadata and add note
		$metadata = isset( $conversation['conversation_metadata'] ) ? maybe_unserialize( $conversation['conversation_metadata'] ) : array();
		if ( ! is_array( $metadata ) ) {
			$metadata = array();
		}
		
		if ( ! isset( $metadata['notes'] ) ) {
			$metadata['notes'] = array();
		}

		$metadata['notes'][] = array(
			'note' => $note,
			'author' => wp_get_current_user()->display_name,
			'timestamp' => current_time( 'mysql' )
		);

		// Update conversation metadata
		global $wpdb;
		$table_name = $wpdb->prefix . 'aria_conversations';
		
		$result = $wpdb->update(
			$table_name,
			array( 'conversation_metadata' => maybe_serialize( $metadata ) ),
			array( 'id' => $conversation_id ),
			array( '%s' ),
			array( '%d' )
		);

			if ( $result !== false ) {
				wp_send_json_success( array( 'message' => __( 'Note added successfully.', 'aria' ) ) );
			} else {
				wp_send_json_error( array( 'message' => __( 'Failed to add note.', 'aria' ) ) );
			}
		}

		/**
		 * Prepare a conversation row for consumption by the React admin experience.
		 *
		 * @param array $conversation Conversation database row.
		 * @return array Prepared payload.
		 */
		private function prepare_admin_conversation_for_response( $conversation ) {
			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-database.php';

			$conversation_id = isset( $conversation['id'] ) ? intval( $conversation['id'] ) : 0;
			$messages_full   = $conversation_id ? Aria_Database::get_conversation_messages( $conversation_id ) : array();
			$message_count  = count( $messages_full );

			$last_message_entry = $message_count ? end( $messages_full ) : null;
			$last_message       = '';

			if ( $last_message_entry && isset( $last_message_entry['content'] ) ) {
				$last_message = wp_strip_all_tags( $last_message_entry['content'] );
			}

			if ( '' === $last_message && ! empty( $conversation['initial_question'] ) ) {
				$last_message = wp_strip_all_tags( $conversation['initial_question'] );
			}

			$last_message = $last_message ? wp_html_excerpt( $last_message, 160, '…' ) : __( 'No messages yet.', 'aria' );

			$metadata = array();
			if ( isset( $conversation['conversation_metadata'] ) ) {
				$maybe_metadata = maybe_unserialize( $conversation['conversation_metadata'] );

				if ( is_array( $maybe_metadata ) ) {
					$metadata = $maybe_metadata;
				}
			}

			$source = __( 'Website Widget', 'aria' );
			if ( ! empty( $metadata['source'] ) ) {
				$source = sanitize_text_field( $metadata['source'] );
			}

			$tags = array();

			if ( ! empty( $metadata['tags'] ) ) {
				$raw_tags = is_array( $metadata['tags'] ) ? $metadata['tags'] : explode( ',', $metadata['tags'] );

				foreach ( $raw_tags as $tag ) {
					$tag = sanitize_text_field( trim( $tag ) );

					if ( '' !== $tag ) {
						$tags[] = $tag;
					}
				}
			}

			if ( ! empty( $conversation['requires_human_review'] ) ) {
				$tags[] = __( 'Needs Review', 'aria' );
			}

			if ( ! empty( $conversation['lead_score'] ) ) {
				$tags[] = sprintf( __( 'Lead %d', 'aria' ), (int) $conversation['lead_score'] );
			}

			if ( ! empty( $conversation['satisfaction_rating'] ) ) {
				$tags[] = sprintf( __( 'Rating %d', 'aria' ), (int) $conversation['satisfaction_rating'] );
			}

			$messages_for_response = $this->prepare_conversation_messages_for_response( $messages_full );

			return array(
				'id'            => $conversation_id,
				'visitor_name'  => isset( $conversation['guest_name'] ) ? sanitize_text_field( $conversation['guest_name'] ) : '',
				'visitor_email' => isset( $conversation['guest_email'] ) ? sanitize_email( $conversation['guest_email'] ) : '',
				'created_at'    => $this->format_datetime_for_display( isset( $conversation['created_at'] ) ? $conversation['created_at'] : '' ),
				'message_count' => $message_count,
				'last_message'  => $last_message,
				'status'        => isset( $conversation['status'] ) ? sanitize_text_field( $conversation['status'] ) : 'active',
				'source'        => $source,
				'tags'          => array_values( array_unique( array_filter( $tags ) ) ),
				'messages'      => $messages_for_response,
			);
		}

		/**
		 * Prepare conversation messages for JSON response.
		 *
		 * @param array $messages Normalized messages.
		 * @return array
		 */
		private function prepare_conversation_messages_for_response( $messages ) {
			if ( empty( $messages ) || ! is_array( $messages ) ) {
				return array();
			}

			$messages = array_slice( $messages, -50 );
			$prepared = array();

			foreach ( $messages as $message ) {
				$content = isset( $message['content'] ) ? wp_strip_all_tags( $message['content'] ) : '';

				if ( '' === $content ) {
					continue;
				}

				$role   = isset( $message['role'] ) ? $message['role'] : ( isset( $message['sender'] ) ? $message['sender'] : 'aria' );
				$sender = ( 'user' === $role ) ? 'visitor' : 'aria';

				$prepared[] = array(
					'sender'    => $sender,
					'content'   => $content,
					'timestamp' => $this->format_datetime_for_display( isset( $message['timestamp'] ) ? $message['timestamp'] : '' ),
				);
			}

			return $prepared;
		}

		/**
		 * Build aggregate conversation metrics for cards.
		 *
		 * @return array Metric payload.
		 */
		private function calculate_conversation_metrics() {
			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-database.php';

			$total    = Aria_Database::get_conversations_count();
			$active   = Aria_Database::get_conversations_count( array( 'status' => 'active' ) );
			$resolved = Aria_Database::get_conversations_count( array( 'status' => 'resolved' ) );

			global $wpdb;
			$table   = $wpdb->prefix . 'aria_conversations';
			$site_id = get_current_blog_id();
			$avg_seconds = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) FROM $table WHERE site_id = %d AND updated_at IS NOT NULL",
					$site_id
				)
			);
			$avg_time_display = $avg_seconds > 0 ? $this->format_duration_for_display( $avg_seconds ) : __( '—', 'aria' );

			$satisfaction = 0;
			if ( $total > 0 && $resolved >= 0 ) {
				$satisfaction = round( ( $resolved / $total ) * 100 );
			}

			return array(
				'totalConversations'  => (int) $total,
				'activeConversations' => (int) $active,
				'avgResponseTime'     => $avg_time_display,
				'satisfactionRate'    => (int) $satisfaction,
			);
		}

		/**
		 * Format a datetime string based on site preferences.
		 *
		 * @param string $datetime Raw datetime.
		 * @return string
		 */
		private function format_datetime_for_display( $datetime ) {
			if ( empty( $datetime ) ) {
				return '';
			}

			$timestamp = strtotime( $datetime );

			if ( ! $timestamp ) {
				return $datetime;
			}

			return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
		}

		/**
		 * Convert seconds into a friendly duration string.
		 *
		 * @param int $seconds Duration in seconds.
		 * @return string
		 */
		private function format_duration_for_display( $seconds ) {
			if ( $seconds <= 0 ) {
				return __( '—', 'aria' );
			}

			$hours   = (int) floor( $seconds / HOUR_IN_SECONDS );
			$minutes = (int) floor( ( $seconds % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS );

			if ( $hours > 0 ) {
				if ( $minutes > 0 ) {
					return sprintf(
						/* translators: 1: number of hours. 2: number of minutes. */
						__( '%1$dh %2$d m', 'aria' ),
						$hours,
						$minutes
					);
				}

				return sprintf(
					/* translators: %d: number of hours. */
					_n( '%d hour', '%d hours', $hours, 'aria' ),
					$hours
				);
			}

			$minutes = max( 1, $minutes );

			return sprintf(
				/* translators: %d: number of minutes. */
				_n( '%d minute', '%d minutes', $minutes, 'aria' ),
				$minutes
			);
		}

		/**
		 * Provide content indexing data for the React admin experience.
		 */
		public function handle_get_content_indexing_data() {
			if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
			}

			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-content-filter.php';
			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-content-vectorizer.php';

			$filter     = new Aria_Content_Filter();
			$vectorizer = new Aria_Content_Vectorizer();

			$data = $this->collect_content_indexing_data( $filter, $vectorizer );

			wp_send_json_success( $data );
		}

		/**
		 * Persist content indexing settings from the admin UI.
		 */
		public function handle_save_content_indexing_settings() {
			if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
			}

			$auto_index      = isset( $_POST['auto_index'] ) ? filter_var( wp_unslash( $_POST['auto_index'] ), FILTER_VALIDATE_BOOLEAN ) : false;
			$index_frequency = isset( $_POST['index_frequency'] ) ? sanitize_text_field( wp_unslash( $_POST['index_frequency'] ) ) : 'daily';
			$exclude_patterns = isset( $_POST['exclude_patterns'] ) ? sanitize_textarea_field( wp_unslash( $_POST['exclude_patterns'] ) ) : '';
			$max_file_size   = isset( $_POST['max_file_size'] ) ? absint( wp_unslash( $_POST['max_file_size'] ) ) : 0;

			$allowed_frequencies = array( 'hourly', 'daily', 'weekly', 'manual' );
			if ( ! in_array( $index_frequency, $allowed_frequencies, true ) ) {
				$index_frequency = 'daily';
			}

			update_option(
				'aria_content_indexing_settings',
				array(
					'auto_index'       => (bool) $auto_index,
					'index_frequency'   => $index_frequency,
					'exclude_patterns'  => $exclude_patterns,
					'max_file_size'     => $max_file_size,
				)
			);

			wp_send_json_success( array( 'message' => __( 'Indexing settings saved.', 'aria' ) ) );
		}

		/**
		 * Toggle whether a content item should be indexed.
		 */
		public function handle_toggle_content_indexing() {
			if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
			}

			$content_id   = isset( $_POST['content_id'] ) ? absint( wp_unslash( $_POST['content_id'] ) ) : 0;
			$should_index = isset( $_POST['should_index'] ) ? filter_var( wp_unslash( $_POST['should_index'] ), FILTER_VALIDATE_BOOLEAN ) : false;

			$post = get_post( $content_id );
			if ( ! $content_id || ! $post ) {
				wp_send_json_error( array( 'message' => __( 'Invalid content item.', 'aria' ) ) );
			}

			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-content-vectorizer.php';
			$vectorizer = new Aria_Content_Vectorizer();
			$post_type  = $post->post_type;

			if ( $should_index ) {
				delete_post_meta( $content_id, '_aria_exclude_indexing' );

				try {
					$vectorizer->index_content( $content_id, $post_type );
				} catch ( Exception $e ) {
					Aria_Logger::error( 'Aria: Failed to re-index content ' . $content_id . ' - ' . $e->getMessage() );
				}

				wp_send_json_success(
					array(
						'status'  => 'indexed',
						'message' => __( 'Content queued for indexing.', 'aria' ),
					)
				);
			}

			update_post_meta( $content_id, '_aria_exclude_indexing', 1 );
			$vectorizer->remove_content_vectors( $content_id, $post_type );

			wp_send_json_success(
				array(
					'status'  => 'excluded',
					'message' => __( 'Content excluded from indexing.', 'aria' ),
				)
			);
		}

		/**
		 * Collect indexing metrics, items, and settings for the admin UI.
		 *
		 * @param Aria_Content_Filter      $filter     Content filter service.
		 * @param Aria_Content_Vectorizer  $vectorizer Vectorizer service.
		 * @return array
		 */
		private function collect_content_indexing_data( $filter, $vectorizer ) {
			global $wpdb;

			$vectors_table = $wpdb->prefix . 'aria_content_vectors';
			$posts_table   = $wpdb->posts;

			$total_indexable = $filter->count_indexable_content();
			$indexed_total = 0;
			$last_indexed   = '';
			$storage_bytes  = 0;

			$vectors_table_exists = $wpdb->get_var(
				$wpdb->prepare(
					"SHOW TABLES LIKE %s",
					$vectors_table
				)
			);

			$chunk_column_exists = false;
			if ( $vectors_table_exists ) {
				$chunk_column_exists = $wpdb->get_var(
					$wpdb->prepare(
						"SHOW COLUMNS FROM {$vectors_table} LIKE %s",
						'chunk_embedding'
					)
				);

				$indexed_total = (int) $wpdb->get_var(
					"SELECT COUNT(DISTINCT cv.content_id)
					 FROM {$vectors_table} cv
					 INNER JOIN {$posts_table} p ON cv.content_id = p.ID"
				);

				$last_indexed = $wpdb->get_var(
					"SELECT MAX(cv.created_at)
					 FROM {$vectors_table} cv
					 INNER JOIN {$posts_table} p ON cv.content_id = p.ID"
				);

				if ( $chunk_column_exists ) {
					$storage_bytes = (int) $wpdb->get_var(
						"SELECT SUM(CHAR_LENGTH(cv.chunk_embedding))
						 FROM {$vectors_table} cv
						 INNER JOIN {$posts_table} p ON cv.content_id = p.ID"
					);
				}
			}

			$indexed_ids = $wpdb->get_col(
				"SELECT DISTINCT cv.content_id
				 FROM {$vectors_table} cv
				 INNER JOIN {$posts_table} p ON cv.content_id = p.ID
				 ORDER BY cv.created_at DESC
				 LIMIT 200"
			);

			$items = array();

			foreach ( $indexed_ids as $content_id ) {
				$post = get_post( $content_id );
				if ( ! $post ) {
					continue;
				}

				$indexed_date = $wpdb->get_var( $wpdb->prepare( "SELECT created_at FROM {$vectors_table} WHERE content_id = %d ORDER BY created_at DESC LIMIT 1", $content_id ) );
				$items[ $content_id ] = $this->prepare_content_indexing_item(
					$post,
					'indexed',
					array( 'indexed_at' => $indexed_date )
				);
			}

			$pending_posts = $filter->get_public_content_batch( 200, 0 );
			foreach ( $pending_posts as $pending_post ) {
				if ( isset( $items[ $pending_post->ID ] ) ) {
					continue;
				}

				$status = get_post_meta( $pending_post->ID, '_aria_exclude_indexing', true ) ? 'excluded' : 'pending';
				$items[ $pending_post->ID ] = $this->prepare_content_indexing_item( $pending_post, $status );
			}

			$excluded_posts = get_posts(
				array(
					'post_type'      => $filter->get_indexable_content_types(),
					'post_status'    => 'publish',
					'posts_per_page' => 200,
					'meta_key'       => '_aria_exclude_indexing',
					'meta_value'     => 1,
				)
			);

			foreach ( $excluded_posts as $excluded_post ) {
				if ( isset( $items[ $excluded_post->ID ] ) ) {
					$items[ $excluded_post->ID ]['status'] = 'excluded';
					continue;
				}

				$items[ $excluded_post->ID ] = $this->prepare_content_indexing_item( $excluded_post, 'excluded' );
			}

			$status_counts = array(
				'indexed'  => 0,
				'pending'  => 0,
				'excluded' => 0,
			);

			foreach ( $items as $item ) {
				if ( isset( $status_counts[ $item['status'] ] ) ) {
					$status_counts[ $item['status'] ]++;
				}
			}

			$metrics = array(
				array(
					'icon'     => 'stack',
					'title'    => __( 'Total items', 'aria' ),
					'value'    => number_format_i18n( $total_indexable ),
					'subtitle' => __( 'Tracked content', 'aria' ),
					'theme'    => 'primary',
				),
				array(
					'icon'     => 'check',
					'title'    => __( 'Indexed items', 'aria' ),
					'value'    => number_format_i18n( $indexed_total ),
					'subtitle' => __( 'Ready for AI', 'aria' ),
					'theme'    => 'success',
				),
				array(
					'icon'     => 'clock',
					'title'    => __( 'Last indexed', 'aria' ),
					'value'    => $last_indexed ? $this->format_datetime_for_display( $last_indexed ) : __( 'Never', 'aria' ),
					'subtitle' => __( 'Most recent run', 'aria' ),
					'theme'    => 'info',
				),
				array(
					'icon'     => 'storage',
					'title'    => __( 'Storage used', 'aria' ),
					'value'    => $storage_bytes > 0 ? size_format( $storage_bytes, 2 ) : '0 MB',
					'subtitle' => __( 'Vector store footprint', 'aria' ),
					'theme'    => 'warning',
				),
			);

			$settings = get_option( 'aria_content_indexing_settings', array() );
			$settings = wp_parse_args(
				$settings,
				array(
					'auto_index'      => true,
					'index_frequency'  => 'daily',
					'exclude_patterns' => '',
					'max_file_size'    => 10,
				)
			);

			return array(
				'metrics'        => $metrics,
				'items'          => array_values( $items ),
				'settings'       => array(
					'autoIndex'       => (bool) $settings['auto_index'],
					'indexFrequency'  => $settings['index_frequency'],
					'excludePatterns' => (string) $settings['exclude_patterns'],
					'maxFileSize'     => (string) $settings['max_file_size'],
				),
				'availableTypes' => $this->prepare_indexable_types_for_response( $filter->get_indexable_content_types() ),
				'statusCounts'   => $status_counts,
			);
		}

		/**
		 * Prepare a single content item payload.
		 *
		 * @param WP_Post $post    Post object.
		 * @param string  $status  Item status.
		 * @param array   $context Additional context data.
		 * @return array
		 */
		private function prepare_content_indexing_item( $post, $status, $context = array() ) {
			$post_type_label = $this->get_post_type_label_for_response( $post->post_type );
			$word_count      = $this->calculate_word_count_for_post( $post );
			$excerpt         = $this->generate_excerpt_for_post( $post );
			$timestamp       = isset( $context['indexed_at'] ) && $context['indexed_at']
				? $this->format_datetime_for_display( $context['indexed_at'] )
				: $this->format_datetime_for_display( $post->post_modified ?: $post->post_date );

			return array(
				'id'         => $post->ID,
				'title'      => get_the_title( $post ) ?: __( 'Untitled', 'aria' ),
				'type'       => $post->post_type,
				'type_label' => $post_type_label,
				'status'     => $status,
				'url'        => get_permalink( $post ) ?: '',
				'updated_at' => $timestamp,
				'word_count' => $word_count,
				'excerpt'    => $excerpt,
				'tags'       => $this->get_post_terms_for_response( $post->ID ),
			);
		}

		/**
		 * Retrieve a human-friendly label for a post type.
		 *
		 * @param string $post_type Post type slug.
		 * @return string
		 */
		private function get_post_type_label_for_response( $post_type ) {
			$post_type_object = get_post_type_object( $post_type );
			if ( $post_type_object && isset( $post_type_object->labels->singular_name ) ) {
				return $post_type_object->labels->singular_name;
			}

			return ucfirst( $post_type );
		}

		/**
		 * Collect taxonomy terms for display.
		 *
		 * @param int $post_id Post ID.
		 * @return array
		 */
		private function get_post_terms_for_response( $post_id ) {
			$terms = wp_get_post_terms( $post_id, array( 'category', 'post_tag' ), array( 'fields' => 'names' ) );

			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				return array();
			}

			$terms = array_map( 'sanitize_text_field', $terms );

			return array_slice( array_values( array_unique( $terms ) ), 0, 5 );
		}

		/**
		 * Estimate word count for a post.
		 *
		 * @param WP_Post $post Post object.
		 * @return int
		 */
		private function calculate_word_count_for_post( $post ) {
			$content = $post->post_content;

			if ( empty( $content ) ) {
				return 0;
			}

			$sanitized = wp_strip_all_tags( $content );

			return max( 0, str_word_count( $sanitized ) );
		}

		/**
		 * Generate a short excerpt for display in the UI.
		 *
		 * @param WP_Post $post Post object.
		 * @return string
		 */
		private function generate_excerpt_for_post( $post ) {
			if ( has_excerpt( $post ) ) {
				return wp_trim_words( $post->post_excerpt, 30, '…' );
			}

			$content = wp_strip_all_tags( $post->post_content );

			if ( '' === $content ) {
				return __( 'No content available.', 'aria' );
			}

			return wp_trim_words( $content, 30, '…' );
		}

		/**
		 * Prepare indexable post types for filter controls.
		 *
		 * @param array $post_types Post type slugs.
		 * @return array
		 */
		private function prepare_indexable_types_for_response( $post_types ) {
			$options = array(
				array(
					'label' => __( 'All types', 'aria' ),
					'value' => 'all',
				),
			);

			foreach ( $post_types as $post_type ) {
				$options[] = array(
					'label' => $this->get_post_type_label_for_response( $post_type ),
					'value' => $post_type,
				);
			}

			return $options;
		}

		/**
		 * Handle reindex all content AJAX request.
		 */
		public function handle_reindex_all_content() {
			// Verify nonce and capability
			$nonce_valid = check_ajax_referer( 'aria_content_nonce', 'nonce', false );
			if ( ! $nonce_valid ) {
				$nonce_valid = check_ajax_referer( 'aria_admin_nonce', 'nonce', false );
			}

			if ( ! $nonce_valid || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
			}

		try {
			// Check if AI provider is configured
			$ai_provider = get_option( 'aria_ai_provider', 'openai' );
			$encrypted_api_key = get_option( 'aria_ai_api_key', '' );
			
			if ( empty( $encrypted_api_key ) ) {
				wp_send_json_error( array( 
					'message' => __( 'Please configure your AI provider API key in the AI Configuration page before indexing content.', 'aria' ),
					'action_needed' => 'configure_api'
				) );
			}
			
			// Decrypt the API key
			try {
				require_once ARIA_PLUGIN_PATH . 'includes/class-aria-security.php';
				
				if ( ! class_exists( 'Aria_Security' ) ) {
					wp_send_json_error( array( 
						'message' => __( 'Security class not found. Please check plugin installation.', 'aria' ),
						'action_needed' => 'configure_api'
					) );
				}
				
				$api_key = Aria_Security::decrypt( $encrypted_api_key );
				
				if ( empty( $api_key ) ) {
					wp_send_json_error( array( 
						'message' => __( 'Failed to decrypt API key. Please reconfigure your API key in the AI Configuration page.', 'aria' ),
						'action_needed' => 'configure_api'
					) );
				}
			} catch ( Exception $e ) {
				Aria_Logger::error( 'Aria Content Indexing - Decryption error: ' . $e->getMessage() );
				wp_send_json_error( array( 
					'message' => __( 'API key decryption failed. Please reconfigure your API key.', 'aria' ),
					'action_needed' => 'configure_api'
				) );
			}

			// Reset indexing progress
			delete_option( 'aria_indexing_offset' );
			delete_option( 'aria_initial_indexing_complete' );

			// Clear existing content vectors
			global $wpdb;
			$table = $wpdb->prefix . 'aria_content_vectors';
			$wpdb->query( "TRUNCATE TABLE $table" );

			// Load required classes for content vectorization
			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-content-vectorizer.php';
			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-content-filter.php';
			
			// Test API connection before starting bulk indexing
			$test_result = $this->test_embedding_generation();
			if ( ! $test_result['success'] ) {
				wp_send_json_error( array( 
					'message' => $test_result['message'],
					'action_needed' => 'fix_api'
				) );
			}

			// Start immediate indexing of first batch to provide instant feedback
			$this->process_immediate_indexing_batch();

			// Schedule additional batches
			wp_schedule_single_event( time() + 10, 'aria_initial_content_indexing' );

			wp_send_json_success( array( 
				'message' => __( 'Content indexing started successfully. Check the details below for progress.', 'aria' ),
				'immediate_results' => true
			) );
		} catch ( Exception $e ) {
			Aria_Logger::error( 'Aria: Reindex error - ' . $e->getMessage() );
			wp_send_json_error( array( 'message' => __( 'Failed to start reindexing: ', 'aria' ) . $e->getMessage() ) );
		}
	}

	/**
	 * Test embedding generation with a simple text.
	 *
	 * @return array Result with success status and message.
	 */
	private function test_embedding_generation() {
		try {
			// Load required class
			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-content-vectorizer.php';
			
			$vectorizer = new Aria_Content_Vectorizer();
			
			// Use reflection to access private method for testing
			$reflection = new ReflectionClass( $vectorizer );
			$method = $reflection->getMethod( 'generate_embedding' );
			$method->setAccessible( true );
			
			$test_text = 'This is a test for content vectorization.';
			$embedding = $method->invoke( $vectorizer, $test_text );
			
			if ( $embedding && is_array( $embedding ) && count( $embedding ) > 0 ) {
				return array( 'success' => true, 'message' => 'API connection successful' );
			} else {
				return array( 'success' => false, 'message' => __( 'API key is invalid or embedding generation failed.', 'aria' ) );
			}
		} catch ( Exception $e ) {
			return array( 'success' => false, 'message' => __( 'API connection failed: ', 'aria' ) . $e->getMessage() );
		}
	}

	/**
	 * Process immediate indexing batch for instant feedback.
	 */
	private function process_immediate_indexing_batch() {
		// Load required classes
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-content-filter.php';
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-content-vectorizer.php';
		
		$filter = new Aria_Content_Filter();
		$vectorizer = new Aria_Content_Vectorizer();

		// Get first 3 items for immediate processing
		$posts = $filter->get_public_content_batch( 3, 0 );
		$success_count = 0;

		foreach ( $posts as $post ) {
			if ( $filter->is_content_public( $post->ID, $post->post_type ) ) {
				$success = $vectorizer->index_content( $post->ID, $post->post_type );
				if ( $success ) {
					$success_count++;
					Aria_Logger::debug( "Aria: Immediately indexed {$post->post_type} {$post->ID}" );
				}
			}
		}

		// Update offset to skip these items in background processing
		if ( count( $posts ) > 0 ) {
			update_option( 'aria_indexing_offset', count( $posts ) );
		}

		Aria_Logger::debug( "Aria: Immediate indexing completed {$success_count} items" );
	}

	/**
	 * Handle test content search AJAX request.
	 */
	public function handle_test_content_search() {
		// Verify nonce and capability
		if ( ! check_ajax_referer( 'aria_content_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		$query = isset( $_POST['query'] ) ? sanitize_text_field( $_POST['query'] ) : '';
		if ( empty( $query ) ) {
			wp_send_json_error( array( 'message' => __( 'Query cannot be empty.', 'aria' ) ) );
		}

		try {
			$content_vectorizer = new Aria_Content_Vectorizer();
			
			$html = '<div class="aria-search-results">';
			$html .= '<h4>' . sprintf( __( 'Deep Analysis for "%s"', 'aria' ), esc_html( $query ) ) . '</h4>';
			
			// Step 1: Test embedding generation for the query
			$html .= '<div class="debug-section">';
			$html .= '<h5>Step 1: Query Embedding Generation</h5>';
			
			// Check API configuration first
			$ai_provider = get_option( 'aria_ai_provider', 'openai' );
			$encrypted_api_key = get_option( 'aria_ai_api_key', '' );
			
			$html .= '<p><strong>AI Provider:</strong> ' . esc_html( $ai_provider ) . '</p>';
			$html .= '<p><strong>API Key Configured:</strong> ' . ( ! empty( $encrypted_api_key ) ? 'Yes' : 'No' ) . '</p>';
			
			if ( empty( $encrypted_api_key ) ) {
				$html .= '<p style="color: red;">❌ No API key configured. Please configure your AI provider in the AI Configuration page.</p>';
				$html .= '</div>';
				$html .= '</div>';
				wp_send_json_success( array( 'html' => $html ) );
				return;
			}
			
			// Use reflection to access private method
			$reflection = new ReflectionClass( $content_vectorizer );
			$method = $reflection->getMethod( 'generate_embedding' );
			$method->setAccessible( true );
			
			try {
				$query_embedding = $method->invoke( $content_vectorizer, $query );
				
				if ( $query_embedding && is_array( $query_embedding ) ) {
					$html .= '<p style="color: green;">✅ Query embedding generated successfully (' . count( $query_embedding ) . ' dimensions)</p>';
					$html .= '<p>First 5 values: ' . implode( ', ', array_slice( $query_embedding, 0, 5 ) ) . '...</p>';
				} else {
					$html .= '<p style="color: red;">❌ Failed to generate embedding for query</p>';
					$html .= '<p>Returned value type: ' . gettype( $query_embedding ) . '</p>';
					if ( is_string( $query_embedding ) ) {
						$html .= '<p>Error message: ' . esc_html( $query_embedding ) . '</p>';
					}
					$html .= '</div>';
					$html .= '</div>';
					wp_send_json_success( array( 'html' => $html ) );
					return;
				}
			} catch ( Exception $e ) {
				$html .= '<p style="color: red;">❌ Exception during embedding generation: ' . esc_html( $e->getMessage() ) . '</p>';
				$html .= '</div>';
				$html .= '</div>';
				wp_send_json_success( array( 'html' => $html ) );
				return;
			}
			$html .= '</div>';
			
			// Step 2: Get all vectors from database
			$html .= '<div class="debug-section">';
			$html .= '<h5>Step 2: Database Vector Analysis</h5>';
			
			global $wpdb;
			$vectors_table = $wpdb->prefix . 'aria_content_vectors';
			$all_vectors = $wpdb->get_results( "SELECT * FROM $vectors_table ORDER BY created_at DESC", ARRAY_A );
			
			$html .= '<p>Total vectors in database: ' . count( $all_vectors ) . '</p>';
			
			if ( empty( $all_vectors ) ) {
				$html .= '<p style="color: red;">❌ No vectors found in database</p>';
				$html .= '</div>';
				$html .= '</div>';
				wp_send_json_success( array( 'html' => $html ) );
				return;
			}
			$html .= '</div>';
			
			// Step 3: Manual similarity calculation with detailed output
			$html .= '<div class="debug-section">';
			$html .= '<h5>Step 3: Manual Similarity Calculations</h5>';
			
			$similarities = array();
			foreach ( $all_vectors as $vector ) {
				$content_vector = json_decode( $vector['content_vector'], true );
				if ( ! $content_vector || ! is_array( $content_vector ) ) {
					continue;
				}
				
				$similarity = $this->calculate_cosine_similarity( $query_embedding, $content_vector );
				$metadata = json_decode( $vector['metadata'], true );
				
				$similarities[] = array(
					'vector' => $vector,
					'similarity' => $similarity,
					'title' => $metadata['title'] ?? 'Untitled',
					'content_preview' => wp_trim_words( $vector['content_text'], 10 )
				);
			}
			
			// Sort by similarity
			usort( $similarities, function( $a, $b ) {
				return $b['similarity'] <=> $a['similarity'];
			});
			
			$html .= '<p>Top 10 similarity scores:</p>';
			$html .= '<table style="width: 100%; border-collapse: collapse; margin: 10px 0;">';
			$html .= '<thead><tr style="background: #f5f5f5;"><th style="padding: 8px; border: 1px solid #ddd;">Similarity</th><th style="padding: 8px; border: 1px solid #ddd;">Title</th><th style="padding: 8px; border: 1px solid #ddd;">Content Preview</th></tr></thead>';
			
			for ( $i = 0; $i < min( 10, count( $similarities ) ); $i++ ) {
				$sim = $similarities[$i];
				$similarity_percent = round( $sim['similarity'] * 100, 3 );
				$html .= '<tr>';
				$html .= '<td style="padding: 8px; border: 1px solid #ddd;">' . $similarity_percent . '%</td>';
				$html .= '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html( $sim['title'] ) . '</td>';
				$html .= '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html( $sim['content_preview'] ) . '</td>';
				$html .= '</tr>';
			}
			$html .= '</table>';
			$html .= '</div>';
			
			// Step 4: Look specifically for the Italian restaurant content
			$html .= '<div class="debug-section">';
			$html .= '<h5>Step 4: Italian Restaurant Content Analysis</h5>';
			
			$found_italian = false;
			foreach ( $similarities as $sim ) {
				if ( stripos( $sim['title'], 'mangia' ) !== false || stripos( $sim['content_preview'], 'mangia' ) !== false ) {
					$found_italian = true;
					$similarity_percent = round( $sim['similarity'] * 100, 3 );
					$html .= '<p style="color: blue;">🔍 Found Italian content with ' . $similarity_percent . '% similarity:</p>';
					$html .= '<p><strong>Title:</strong> ' . esc_html( $sim['title'] ) . '</p>';
					$html .= '<p><strong>Content:</strong> ' . esc_html( $sim['vector']['content_text'] ) . '</p>';
					
					// Step 5: Deep embedding comparison
					$html .= '<h6>🔬 Deep Embedding Analysis</h6>';
					
					// Check if "Bevi" appears in the content
					$content_lower = strtolower( $sim['vector']['content_text'] );
					$query_lower = strtolower( $query );
					
					if ( strpos( $content_lower, $query_lower ) !== false ) {
						$html .= '<p style="color: green;">✅ "' . esc_html( $query ) . '" found in content (exact match)</p>';
					} else {
						$html .= '<p style="color: orange;">⚠️ "' . esc_html( $query ) . '" not found as exact match in content</p>';
					}
					
					// Test different variations
					$variations = array( 
						$query,
						strtolower( $query ),
						ucfirst( strtolower( $query ) ),
						strtoupper( $query ),
						$query . '.',
						$query . ',',
					);
					
					$html .= '<p><strong>Testing query variations:</strong></p>';
					$html .= '<ul>';
					foreach ( $variations as $variation ) {
						$var_embedding = $method->invoke( $content_vectorizer, $variation );
						if ( $var_embedding ) {
							$content_vector = json_decode( $sim['vector']['content_vector'], true );
							$var_similarity = $this->calculate_cosine_similarity( $var_embedding, $content_vector );
							$var_percent = round( $var_similarity * 100, 3 );
							$html .= '<li>"' . esc_html( $variation ) . '": ' . $var_percent . '%</li>';
						}
					}
					$html .= '</ul>';
					
					// Test if individual words in the content get better matches
					$content_words = explode( ' ', $sim['vector']['content_text'] );
					$word_similarities = array();
					
					foreach ( $content_words as $word ) {
						$clean_word = trim( strtolower( preg_replace( '/[^\w\s]/', '', $word ) ) );
						if ( strlen( $clean_word ) > 2 ) {
							$word_embedding = $method->invoke( $content_vectorizer, $clean_word );
							if ( $word_embedding ) {
								$word_similarity = $this->calculate_cosine_similarity( $query_embedding, $word_embedding );
								$word_similarities[$clean_word] = $word_similarity;
							}
						}
					}
					
					// Show top 5 word matches
					arsort( $word_similarities );
					$top_words = array_slice( $word_similarities, 0, 5, true );
					
					$html .= '<p><strong>Top word similarities from content:</strong></p>';
					$html .= '<ul>';
					foreach ( $top_words as $word => $similarity ) {
						$word_percent = round( $similarity * 100, 3 );
						$html .= '<li>"' . esc_html( $word ) . '": ' . $word_percent . '%</li>';
					}
					$html .= '</ul>';
					
					break;
				}
			}
			
			if ( ! $found_italian ) {
				$html .= '<p style="color: red;">❌ No Italian restaurant content found in top results</p>';
			}
			
			$html .= '</div>';
			$html .= '</div>';

			wp_send_json_success( array( 'html' => $html ) );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => __( 'Search test failed.', 'aria' ) ) );
		}
	}

	/**
	 * Handle clear search cache AJAX request.
	 */
	public function handle_clear_search_cache() {
		// Verify nonce and capability
		if ( ! check_ajax_referer( 'aria_content_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		try {
			// Clear WordPress object cache
			wp_cache_flush();

			// Clear any custom search cache if it exists
			global $wpdb;
			$cache_table = $wpdb->prefix . 'aria_search_cache';
			if ( $wpdb->get_var( "SHOW TABLES LIKE '$cache_table'" ) === $cache_table ) {
				$wpdb->query( "TRUNCATE TABLE $cache_table" );
			}

			wp_send_json_success( array( 'message' => __( 'Search cache cleared successfully.', 'aria' ) ) );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => __( 'Failed to clear cache.', 'aria' ) ) );
		}
	}

	/**
	 * Handle save content settings AJAX request.
	 */
	public function handle_save_content_settings() {
		// Verify nonce and capability
		if ( ! check_ajax_referer( 'aria_content_settings', 'aria_content_nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		try {
			$excluded_types = isset( $_POST['excluded_content_types'] ) ? array_map( 'sanitize_text_field', $_POST['excluded_content_types'] ) : array();
			
			// Save excluded content types
			update_option( 'aria_excluded_content_types', $excluded_types );

			// Log privacy action
			$content_filter = new Aria_Content_Filter();
			$content_filter->log_privacy_action( 'settings_updated', array(
				'excluded_types' => $excluded_types,
				'user_id' => get_current_user_id(),
			) );

			wp_send_json_success( array( 'message' => __( 'Content settings saved successfully.', 'aria' ) ) );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => __( 'Failed to save settings.', 'aria' ) ) );
		}
	}

	/**
	 * Handle individual content indexing.
	 */
	public function handle_index_single_item() {
		// Verify nonce and capability
		if ( ! check_ajax_referer( 'aria_content_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		$content_id = absint( $_POST['content_id'] ?? 0 );
		$content_type = sanitize_text_field( $_POST['content_type'] ?? '' );

		if ( ! $content_id || ! $content_type ) {
			wp_send_json_error( array( 'message' => __( 'Invalid content ID or type.', 'aria' ) ) );
		}

		// Verify content exists and is public
		$content_filter = new Aria_Content_Filter();
		if ( ! $content_filter->is_content_public( $content_id, $content_type ) ) {
			wp_send_json_error( array( 'message' => __( 'Content is not public or does not exist.', 'aria' ) ) );
		}

		try {
			$content_vectorizer = new Aria_Content_Vectorizer();
			$success = $content_vectorizer->index_content( $content_id, $content_type );

			if ( $success ) {
				$post_title = get_the_title( $content_id );
				$message = sprintf( 
					__( 'Successfully indexed "%s" (%s).', 'aria' ), 
					$post_title ?: __( 'Untitled', 'aria' ),
					ucfirst( $content_type )
				);
				wp_send_json_success( array( 'message' => $message ) );
			} else {
				wp_send_json_error( array( 'message' => __( 'Failed to index content. Please check your API configuration.', 'aria' ) ) );
			}
		} catch ( Exception $e ) {
			Aria_Logger::error( "Aria: Failed to index {$content_type} {$content_id}: " . $e->getMessage() );
			wp_send_json_error( array( 'message' => __( 'Failed to index content. Please try again.', 'aria' ) ) );
		}
	}

	/**
	 * Handle debug vectors AJAX request.
	 */
	public function handle_debug_vectors() {
		// Verify nonce and capability
		if ( ! check_ajax_referer( 'aria_content_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		try {
			global $wpdb;
			$vectors_table = $wpdb->prefix . 'aria_content_vectors';
			
			// Get all vectors from database
			$vectors = $wpdb->get_results( "SELECT * FROM $vectors_table ORDER BY created_at DESC LIMIT 50", ARRAY_A );
			
			$html = '<div class="aria-debug-vectors">';
			
			if ( ! empty( $vectors ) ) {
				$html .= '<h4>' . sprintf( __( 'Debug: %d Content Vectors (showing latest 50)', 'aria' ), count( $vectors ) ) . '</h4>';
				$html .= '<div class="debug-vectors-list">';
				
				foreach ( $vectors as $vector ) {
					$metadata = json_decode( $vector['metadata'], true );
					$content_vector = json_decode( $vector['content_vector'], true );
					
					$html .= '<div class="debug-vector-item">';
					$html .= '<div class="vector-header">';
					$html .= '<strong>' . esc_html( $metadata['title'] ?? 'Untitled' ) . '</strong>';
					$html .= '<span class="vector-type">(' . esc_html( $vector['content_type'] ) . ' #' . $vector['content_id'] . ', chunk ' . $vector['chunk_index'] . ')</span>';
					$html .= '</div>';
					
					$html .= '<div class="vector-text">';
					$html .= '<strong>Text:</strong> ' . esc_html( wp_trim_words( $vector['content_text'], 30 ) );
					$html .= '</div>';
					
					$html .= '<div class="vector-info">';
					$html .= '<span class="vector-date">Indexed: ' . esc_html( human_time_diff( strtotime( $vector['created_at'] ) ) ) . ' ago</span>';
					$html .= '<span class="vector-size">Vector size: ' . ( is_array( $content_vector ) ? count( $content_vector ) : 'Invalid' ) . ' dimensions</span>';
					$html .= '</div>';
					
					$html .= '</div>';
				}
				
				$html .= '</div>';
			} else {
				$html .= '<p>' . __( 'No content vectors found in database.', 'aria' ) . '</p>';
			}
			
			$html .= '</div>';
			
			wp_send_json_success( array( 'html' => $html ) );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => __( 'Failed to debug vectors.', 'aria' ) ) );
		}
	}

	/**
	 * Calculate cosine similarity between two vectors.
	 *
	 * @param array $vector1 First vector.
	 * @param array $vector2 Second vector.
	 * @return float Similarity score (0-1).
	 */
	private function calculate_cosine_similarity( $vector1, $vector2 ) {
		if ( count( $vector1 ) !== count( $vector2 ) ) {
			return 0;
		}

		$dot_product = 0;
		$magnitude1 = 0;
		$magnitude2 = 0;

		for ( $i = 0; $i < count( $vector1 ); $i++ ) {
			$dot_product += $vector1[ $i ] * $vector2[ $i ];
			$magnitude1 += $vector1[ $i ] * $vector1[ $i ];
			$magnitude2 += $vector2[ $i ] * $vector2[ $i ];
		}

		$magnitude1 = sqrt( $magnitude1 );
		$magnitude2 = sqrt( $magnitude2 );

		if ( $magnitude1 == 0 || $magnitude2 == 0 ) {
			return 0;
		}

		return $dot_product / ( $magnitude1 * $magnitude2 );
	}

	/**
	 * Handle get dashboard data AJAX request.
	 */
	public function handle_get_dashboard_data() {
		// Verify nonce and capability
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'aria_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'aria' ) ) );
		}

		try {
			// Get real dashboard data
			$dashboard_data = $this->get_real_dashboard_data();
			
			// Debug logging for dashboard data
			Aria_Logger::debug( 'Aria Dashboard Data Retrieved: ' . wp_json_encode( $dashboard_data ) );
			
			wp_send_json_success( $dashboard_data );
		} catch ( Exception $e ) {
			Aria_Logger::error( 'Aria Dashboard Data Error: ' . $e->getMessage() );
			Aria_Logger::error( 'Aria Dashboard Data Error Stack: ' . $e->getTraceAsString() );
			wp_send_json_error( array( 'message' => __( 'Failed to load dashboard data.', 'aria' ) ) );
		}
	}

	/**
	 * Get advanced settings for admin UI.
	 */
	public function handle_get_advanced_settings() {
		if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		$defaults = array(
			'cache_responses' => true,
			'cache_duration'  => 3600,
			'rate_limit'      => 60,
		);

		$settings = get_option( 'aria_advanced_settings', array() );
		$settings = wp_parse_args( $settings, $defaults );

		wp_send_json_success(
			array(
				'cacheResponses' => (bool) $settings['cache_responses'],
				'cacheDuration'  => (string) absint( $settings['cache_duration'] ),
				'rateLimit'      => (string) absint( $settings['rate_limit'] ),
				'debugLogging'   => (bool) get_option( Aria_Logger::OPTION_DEBUG_LOGGING, false ),
			)
		);
	}

	/**
	 * Save advanced settings from admin UI.
	 */
	public function handle_save_advanced_settings() {
		if ( ! check_ajax_referer( 'aria_admin_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		$debug_logging   = isset( $_POST['debug_logging'] ) ? filter_var( wp_unslash( $_POST['debug_logging'] ), FILTER_VALIDATE_BOOLEAN ) : false;
		$cache_responses = isset( $_POST['cache_responses'] ) ? filter_var( wp_unslash( $_POST['cache_responses'] ), FILTER_VALIDATE_BOOLEAN ) : true;
		$cache_duration  = isset( $_POST['cache_duration'] ) ? max( 0, absint( $_POST['cache_duration'] ) ) : 3600;
		$rate_limit      = isset( $_POST['rate_limit'] ) ? max( 0, absint( $_POST['rate_limit'] ) ) : 60;

		update_option( Aria_Logger::OPTION_DEBUG_LOGGING, $debug_logging );
		update_option(
			'aria_advanced_settings',
			array(
				'cache_responses' => $cache_responses,
				'cache_duration'  => $cache_duration,
				'rate_limit'      => $rate_limit,
			)
		);

		wp_send_json_success( array( 'message' => __( 'Advanced settings updated.', 'aria' ) ) );
	}

	/**
	 * Get real dashboard data from database.
	 *
	 * @return array Dashboard data.
	 */
	private function get_real_dashboard_data() {
		global $wpdb;

		// Get analytics data
		$today_start = date( 'Y-m-d 00:00:00' );
		$yesterday_start = date( 'Y-m-d 00:00:00', strtotime( '-1 day' ) );

		// Get conversation counts with enhanced debugging
		$total_conversations = Aria_Database::get_conversations_count();
		$conversations_today = Aria_Database::get_conversations_count( array(
			'date_from' => $today_start,
		) );
		$conversations_yesterday = Aria_Database::get_conversations_count( array(
			'date_from' => $yesterday_start,
			'date_to' => $today_start,
		) );
		
		// Debug logging for conversation counts
		Aria_Logger::debug( "Aria Conversation Counts - Total: $total_conversations, Today: $conversations_today, Yesterday: $conversations_yesterday" );
		Aria_Logger::debug( "Aria Date Filters - Today Start: $today_start, Yesterday Start: $yesterday_start" );
		
		// Check for any actual conversation data to debug fake data issues
		global $wpdb;
		$sample_conversations = $wpdb->get_results( $wpdb->prepare( 
			"SELECT id, guest_name, guest_email, initial_question, created_at, status FROM {$wpdb->prefix}aria_conversations WHERE site_id = %d ORDER BY created_at DESC LIMIT 5",
			get_current_blog_id()
		), ARRAY_A );
		Aria_Logger::debug( "Aria Sample Conversations in DB: " . wp_json_encode( $sample_conversations ) );

		// Calculate conversation growth
		$conversation_growth = 0;
		if ( $conversations_yesterday > 0 ) {
			$conversation_growth = round( ( ( $conversations_today - $conversations_yesterday ) / $conversations_yesterday ) * 100, 1 );
		}

		// Get knowledge entries (check both new and legacy tables)
		$knowledge_entries = Aria_Database::get_knowledge_entries( array( 'limit' => 9999 ) );
		$knowledge_count = is_array( $knowledge_entries ) ? count( $knowledge_entries ) : 0;
		
		// Also check legacy knowledge base table for content
		$legacy_table = $wpdb->prefix . 'aria_knowledge_base';
		$legacy_count = (int) $wpdb->get_var( $wpdb->prepare( 
			"SELECT COUNT(*) FROM $legacy_table WHERE site_id = %d",
			get_current_blog_id()
		) );
		
		// Also check WordPress content vectorization system using established method
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-content-vectorizer.php';
		$content_vectorizer = new Aria_Content_Vectorizer();
		$indexing_stats = $content_vectorizer->get_indexing_stats();
		$total_vectors = $indexing_stats['total_vectors'] ?? 0;
		
		// For dashboard display, show unique content items (approximate by dividing by average chunks per item)
		// Or we can show total vectors - let's use total vectors for now since that's what content indexing shows
		$vectorized_content_count = $total_vectors;
		
		// Use the total count from all knowledge sources
		$manual_knowledge_count = $knowledge_count + $legacy_count;
		$total_knowledge_count = $manual_knowledge_count + $vectorized_content_count;
		
		// Enhanced debug logging for all knowledge sources
		Aria_Logger::debug( "Aria Knowledge Sources Debug:" );
		Aria_Logger::debug( "  - Manual entries (new table): $knowledge_count" );
		Aria_Logger::debug( "  - Manual entries (legacy table): $legacy_count" );
		Aria_Logger::debug( "  - WordPress vectorized content (total vectors): $vectorized_content_count" );
		Aria_Logger::debug( "  - Indexing stats: " . wp_json_encode( $indexing_stats ) );
		Aria_Logger::debug( "  - Total manual knowledge: $manual_knowledge_count" );
		Aria_Logger::debug( "  - TOTAL KNOWLEDGE COUNT: $total_knowledge_count" );
		
		// Sample data logging
		if ( !empty( $knowledge_entries ) ) {
			Aria_Logger::debug( "Aria Knowledge Sample (new): " . wp_json_encode( array_slice( $knowledge_entries, 0, 2 ) ) );
		}
		if ( $legacy_count > 0 ) {
			$legacy_sample = $wpdb->get_results( $wpdb->prepare(
				"SELECT id, title, LEFT(content, 100) as content_preview FROM $legacy_table WHERE site_id = %d LIMIT 2",
				get_current_blog_id()
			), ARRAY_A );
			Aria_Logger::debug( "Aria Knowledge Sample (legacy): " . wp_json_encode( $legacy_sample ) );
		}
		if ( $vectorized_content_count > 0 ) {
			$content_vectors_table = $wpdb->prefix . 'aria_content_vectors';
			$vectorized_sample = $wpdb->get_results( 
				"SELECT content_id, content_type, LEFT(content_text, 100) as content_preview 
				 FROM $content_vectors_table 
				 ORDER BY id DESC LIMIT 3",
				ARRAY_A 
			);
			Aria_Logger::debug( "Aria Vectorized Content Sample: " . wp_json_encode( $vectorized_sample ) );
		}

		// Get recent knowledge entries (last 7 days)
		$recent_knowledge_count = 0;
		if ( is_array( $knowledge_entries ) ) {
			$seven_days_ago = date( 'Y-m-d H:i:s', strtotime( '-7 days' ) );
			foreach ( $knowledge_entries as $entry ) {
				if ( isset( $entry['created_at'] ) && $entry['created_at'] >= $seven_days_ago ) {
					$recent_knowledge_count++;
				}
			}
		}

		// Calculate average response quality from actual data
		// For now, we'll calculate based on conversation completion rate
		$avg_response_quality = 0;
		$quality_trend = 0;
		if ( $total_conversations > 0 ) {
			// Calculate completion rate as a proxy for quality
			$completed_conversations = Aria_Database::get_conversations_count( array( 'status' => 'completed' ) );
			$avg_response_quality = round( ( $completed_conversations / $total_conversations ) * 100 );
			
			// Calculate trend based on last 7 days vs previous 7 days
			$week_ago = date( 'Y-m-d 00:00:00', strtotime( '-7 days' ) );
			$two_weeks_ago = date( 'Y-m-d 00:00:00', strtotime( '-14 days' ) );
			
			$recent_completed = Aria_Database::get_conversations_count( array( 
				'status' => 'completed',
				'date_from' => $week_ago 
			) );
			$previous_completed = Aria_Database::get_conversations_count( array( 
				'status' => 'completed',
				'date_from' => $two_weeks_ago,
				'date_to' => $week_ago
			) );
			
			if ( $previous_completed > 0 ) {
				$quality_trend = $recent_completed - $previous_completed;
			}
		}

		// Calculate response time from actual conversation data
		// For now, we'll use a default since we need to track actual response times
		$avg_response_time = null; // No data available yet
		$response_time_trend = null;

		// Get license status
		$admin = new Aria_Admin( 'aria', ARIA_VERSION );
		$license_method = new ReflectionMethod( 'Aria_Admin', 'get_license_status' );
		$license_method->setAccessible( true );
		$license_status = $license_method->invoke( $admin );

		// Get setup completion status
		$api_configured = ! empty( get_option( 'aria_ai_api_key' ) );
		$personality_configured = get_option( 'aria_personality_configured', false );
		$design_configured = get_option( 'aria_design_configured', false );

		// Calculate setup progress
		$setup_steps_total = 4;
		$setup_steps_completed = 0;
		if ( $api_configured ) $setup_steps_completed++;
		if ( $knowledge_count > 0 ) $setup_steps_completed++;
		if ( $personality_configured ) $setup_steps_completed++;
		if ( $design_configured ) $setup_steps_completed++;
		$setup_progress = round( ( $setup_steps_completed / $setup_steps_total ) * 100 );

		// Get recent conversations
		$recent_conversations = Aria_Database::get_conversations( array( 'limit' => 5 ) );
		
		// Debug logging for conversations
		Aria_Logger::debug( 'Aria Recent Conversations Raw: ' . wp_json_encode( $recent_conversations ) );

		// Format conversations for React component
		$formatted_conversations = array();
		if ( ! empty( $recent_conversations ) ) {
			foreach ( $recent_conversations as $conversation ) {
				$formatted_conversations[] = array(
					'id' => $conversation['id'],
					'guest_name' => $conversation['guest_name'] ?: 'Anonymous',
					'status' => $conversation['status'] ?: 'closed',
					'initial_question' => $conversation['initial_question'] ?: 'No message',
					'created_at' => $conversation['created_at'],
					'messages_count' => isset( $conversation['messages_count'] ) ? $conversation['messages_count'] : 0,
				);
			}
		}
		
		// Debug logging for formatted conversations
		Aria_Logger::debug( 'Aria Formatted Conversations: ' . wp_json_encode( $formatted_conversations ) );

		// Build setup steps
		$setup_steps = array(
			array(
				'completed' => $api_configured,
				'title' => __( 'AI Provider', 'aria' ),
				'icon' => 'admin-settings',
				'link' => admin_url( 'admin.php?page=aria-ai-config' ),
			),
			array(
				'completed' => $knowledge_count > 0,
				'title' => __( 'Knowledge Base', 'aria' ),
				'icon' => 'book',
				'link' => admin_url( 'admin.php?page=aria-knowledge' ),
			),
			array(
				'completed' => $personality_configured,
				'title' => __( 'Personality', 'aria' ),
				'icon' => 'admin-appearance',
				'link' => admin_url( 'admin.php?page=aria-personality' ),
			),
			array(
				'completed' => $design_configured,
				'title' => __( 'Appearance', 'aria' ),
				'icon' => 'admin-customizer',
				'link' => admin_url( 'admin.php?page=aria-design' ),
			),
		);

		// Final dashboard data structure before returning to React
		$final_data = array(
			'conversationsToday' => $conversations_today,
			'totalConversations' => $total_conversations,
			'conversationGrowth' => $conversation_growth,
			'knowledgeCount' => $total_knowledge_count,
			'recentKnowledgeCount' => $recent_knowledge_count,
			'avgResponseQuality' => $avg_response_quality,
			'qualityTrend' => $quality_trend,
			'avgResponseTime' => $avg_response_time,
			'responseTimeTrend' => $response_time_trend,
			'licenseStatus' => $license_status,
			'setupProgress' => $setup_progress,
			'setupStepsCompleted' => $setup_steps_completed,
			'setupStepsTotal' => $setup_steps_total,
			'recentConversations' => $formatted_conversations,
			'setupSteps' => $setup_steps,
			'apiConfigured' => $api_configured,
			'personalityConfigured' => $personality_configured,
			'designConfigured' => $design_configured,
		);

		// Data consistency validation
		$this->validate_dashboard_data_consistency( $final_data );

		// Final debug log - what's actually being sent to React
		Aria_Logger::debug( "=== ARIA DASHBOARD FINAL DATA BEING SENT TO REACT ===" );
		Aria_Logger::debug( "Conversations Today: {$final_data['conversationsToday']}" );
		Aria_Logger::debug( "Total Conversations: {$final_data['totalConversations']}" );
		Aria_Logger::debug( "Knowledge Count: {$final_data['knowledgeCount']}" );
		Aria_Logger::debug( "Recent Conversations Count: " . count( $final_data['recentConversations'] ) );
		Aria_Logger::debug( "API Configured: " . ( $final_data['apiConfigured'] ? 'YES' : 'NO' ) );
		Aria_Logger::debug( "=== END DASHBOARD DATA ===" );

		return $final_data;
	}

	/**
	 * Validate dashboard data consistency across all tables.
	 *
	 * @param array $dashboard_data The dashboard data to validate.
	 */
	private function validate_dashboard_data_consistency( $dashboard_data ) {
		global $wpdb;
		
		Aria_Logger::debug( "=== ARIA DATA CONSISTENCY VALIDATION ===" );
		
		// Check table existence
		$tables_to_check = array(
			'aria_conversations',
			'aria_knowledge_entries', 
			'aria_knowledge_base',
			'aria_content_vectors'
		);
		
		foreach ( $tables_to_check as $table_name ) {
			$full_table_name = $wpdb->prefix . $table_name;
			$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$full_table_name'" );
			
			if ( $table_exists ) {
				$row_count = $wpdb->get_var( "SELECT COUNT(*) FROM $full_table_name" );
				Aria_Logger::debug( "  ✓ Table $table_name exists with $row_count total rows" );
				
				// Check site-specific data for tables that have site_id
				if ( in_array( $table_name, array( 'aria_conversations', 'aria_knowledge_entries', 'aria_knowledge_base' ) ) ) {
					$site_count = $wpdb->get_var( $wpdb->prepare( 
						"SELECT COUNT(*) FROM $full_table_name WHERE site_id = %d",
						get_current_blog_id()
					) );
					Aria_Logger::debug( "    → $site_count rows for current site (ID: " . get_current_blog_id() . ")" );
				}
			} else {
				Aria_Logger::debug( "  ✗ Table $table_name DOES NOT EXIST" );
			}
		}
		
		// Validate knowledge count calculation
		Aria_Logger::debug( "Knowledge Count Breakdown:" );
		Aria_Logger::debug( "  - Dashboard reports: {$dashboard_data['knowledgeCount']} total" );
		
		// Compare with content indexing page method
		if ( class_exists( 'Aria_Content_Vectorizer' ) ) {
			$content_vectorizer = new Aria_Content_Vectorizer();
			$content_indexing_stats = $content_vectorizer->get_indexing_stats();
			Aria_Logger::debug( "  - Content indexing page shows: " . ( $content_indexing_stats['total_vectors'] ?? 0 ) . " vectors" );
		}
		
		// Check WordPress content availability
		$wp_posts_count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type IN ('post', 'page')" );
		Aria_Logger::debug( "  - WordPress has $wp_posts_count published posts/pages available for indexing" );
		
		// Validate conversation counts
		Aria_Logger::debug( "Conversation Count Validation:" );
		Aria_Logger::debug( "  - Dashboard reports: {$dashboard_data['totalConversations']} total, {$dashboard_data['conversationsToday']} today" );
		
		$direct_total = $wpdb->get_var( $wpdb->prepare( 
			"SELECT COUNT(*) FROM {$wpdb->prefix}aria_conversations WHERE site_id = %d",
			get_current_blog_id()
		) );
		Aria_Logger::debug( "  - Direct query shows: $direct_total total conversations for current site" );
		
		Aria_Logger::debug( "=== END VALIDATION ===" );
	}

	/**
	 * Get AI configuration for React component
	 */
	public function handle_get_ai_config() {
		// Verify nonce and permissions
		if ( ! wp_verify_nonce( $_POST['nonce'], 'aria_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'aria' ) ) );
		}

		// Get current settings
		$provider = get_option( 'aria_ai_provider', 'openai' );
		$api_key = get_option( 'aria_ai_api_key', '' );
		$model_settings = get_option( 'aria_ai_model_settings', array() );

		if ( isset( $model_settings['gemini_model'] ) ) {
			require_once ARIA_PLUGIN_PATH . 'includes/providers/class-aria-gemini-provider.php';
			$normalized_gemini_model = Aria_Gemini_Provider::normalize_model_slug( $model_settings['gemini_model'] );
			if ( $normalized_gemini_model !== $model_settings['gemini_model'] ) {
				$model_settings['gemini_model'] = $normalized_gemini_model;
				update_option( 'aria_ai_model_settings', $model_settings );
			}
		}

		// Mask API key for display
		$masked_key = '';
		if ( ! empty( $api_key ) && class_exists( 'Aria_Security' ) ) {
			$decrypted = Aria_Security::decrypt( $api_key );
			if ( $decrypted ) {
				$masked_key = substr( $decrypted, 0, 8 ) . str_repeat( '*', 20 ) . substr( $decrypted, -4 );
			}
		}

		wp_send_json_success( array(
			'provider' => $provider,
			'masked_key' => $masked_key,
			'model_settings' => $model_settings
		) );
	}

	/**
	 * Save AI configuration from React component
	 */
	public function handle_save_ai_config() {
		// Verify nonce and permissions
		if ( ! wp_verify_nonce( $_POST['nonce'], 'aria_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'aria' ) ) );
		}

		$provider = sanitize_text_field( $_POST['provider'] );
		$api_key = sanitize_text_field( $_POST['api_key'] );
		$model_settings = json_decode( stripslashes( $_POST['model_settings'] ), true );

		// Update provider
		update_option( 'aria_ai_provider', $provider );

		// Update API key if provided
		if ( ! empty( $api_key ) && ! strpos( $api_key, '*' ) ) {
			if ( class_exists( 'Aria_Security' ) && Aria_Security::validate_api_key_format( $api_key, $provider ) ) {
				$encrypted_key = Aria_Security::encrypt( $api_key );
				update_option( 'aria_ai_api_key', $encrypted_key );
			} else {
				wp_send_json_error( array( 'message' => __( 'Invalid API key format.', 'aria' ) ) );
			}
		}

		// Update model settings
		if ( is_array( $model_settings ) ) {
			if ( isset( $model_settings['gemini_model'] ) ) {
				require_once ARIA_PLUGIN_PATH . 'includes/providers/class-aria-gemini-provider.php';
				$model_settings['gemini_model'] = Aria_Gemini_Provider::normalize_model_slug( $model_settings['gemini_model'] );
			}

			update_option( 'aria_ai_model_settings', $model_settings );

			// Update specific model options
			if ( $provider === 'openai' && isset( $model_settings['openai_model'] ) ) {
				update_option( 'aria_openai_model', sanitize_text_field( $model_settings['openai_model'] ) );
			}

			if ( isset( $model_settings['gemini_model'] ) ) {
				update_option( 'aria_gemini_model', sanitize_text_field( $model_settings['gemini_model'] ) );
			}
		}

		wp_send_json_success( array( 'message' => __( 'Configuration saved successfully!', 'aria' ) ) );
	}

	/**
	 * Get usage statistics for React component
	 */
	public function handle_get_usage_stats() {
		// Verify nonce and permissions
		if ( ! wp_verify_nonce( $_POST['nonce'], 'aria_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aria' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'aria' ) ) );
		}

		// Get usage statistics
		$current_month = date( 'Y_m' );
		$monthly_usage = get_option( 'aria_ai_usage_' . $current_month, 0 );
		$usage_history = get_option( 'aria_ai_usage_history', array() );
		$recent_activity = array_slice( array_reverse( $usage_history ), 0, 7 );

		// Calculate estimated cost for OpenAI
		$estimated_cost = 0;
		$provider = get_option( 'aria_ai_provider', 'openai' );
		if ( $provider === 'openai' && class_exists( 'Aria_OpenAI_Provider' ) ) {
			$model = get_option( 'aria_openai_model', 'gpt-3.5-turbo' );
			$estimated_cost = Aria_OpenAI_Provider::calculate_cost( $monthly_usage, $model );
		}

		wp_send_json_success( array(
			'monthly_usage' => intval( $monthly_usage ),
			'estimated_cost' => floatval( $estimated_cost ),
			'recent_activity' => $recent_activity
		) );
	}
}
