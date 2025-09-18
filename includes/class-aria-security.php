<?php
/**
 * Security handler
 *
 * @package    Aria
 * @subpackage Aria/includes
 */

/**
 * Handle security operations for the plugin.
 */
class Aria_Security {

	/**
	 * Encrypt sensitive data.
	 *
	 * @param string $data Data to encrypt.
	 * @return string Encrypted data.
	 */
	public static function encrypt( $data ) {
		if ( empty( $data ) ) {
			return '';
		}

		$key = self::get_encryption_key();
		$salt = wp_generate_password( 16, true, true );
		$encrypted = openssl_encrypt( $data, 'AES-256-CBC', $key, 0, $salt );
		
		return base64_encode( $salt . '::' . $encrypted );
	}

	/**
	 * Decrypt sensitive data.
	 *
	 * @param string $data Data to decrypt.
	 * @return string Decrypted data.
	 */
	public static function decrypt( $data ) {
		if ( empty( $data ) ) {
			return '';
		}

		$key = self::get_encryption_key();
		$data = base64_decode( $data );
		
		if ( strpos( $data, '::' ) === false ) {
			return '';
		}

		list( $salt, $encrypted ) = explode( '::', $data, 2 );
		
		return openssl_decrypt( $encrypted, 'AES-256-CBC', $key, 0, $salt );
	}

	/**
	 * Get encryption key.
	 *
	 * @return string Encryption key.
	 */
	private static function get_encryption_key() {
		$key = wp_salt( 'auth' ) . wp_salt( 'secure_auth' );
		return substr( hash( 'sha256', $key ), 0, 32 );
	}

	/**
	 * Validate API key format.
	 *
	 * @param string $api_key API key to validate.
	 * @param string $provider Provider name.
	 * @return bool Valid status.
	 */
	public static function validate_api_key_format( $api_key, $provider ) {
		if ( empty( $api_key ) ) {
			return false;
		}

		switch ( $provider ) {
			case 'openai':
				// OpenAI keys start with 'sk-' and can contain letters, numbers, and hyphens
				// New format can be: sk-proj-xxxxx or sk-xxxxx
				return preg_match( '/^sk-[a-zA-Z0-9\-_]{20,}$/', $api_key ) === 1;

			case 'gemini':
				// Google Gemini API keys can vary in format
				return strlen( $api_key ) >= 30;

			default:
				// Basic validation for unknown providers
				return strlen( $api_key ) >= 20;
		}
	}

	/**
	 * Sanitize conversation input.
	 *
	 * @param string $input User input.
	 * @return string Sanitized input.
	 */
	public static function sanitize_conversation_input( $input ) {
		// Remove any HTML tags
		$input = wp_strip_all_tags( $input );
		
		// Limit length to prevent abuse
		$input = substr( $input, 0, 2000 );
		
		// Remove multiple spaces and trim
		$input = preg_replace( '/\s+/', ' ', $input );
		$input = trim( $input );
		
		return $input;
	}

	/**
	 * Check rate limit for IP.
	 *
	 * @param string $ip IP address.
	 * @param string $action Action being performed.
	 * @param int    $max_attempts Maximum attempts allowed.
	 * @param int    $window Time window in seconds.
	 * @return bool True if within limits.
	 */
	public static function check_rate_limit( $ip = null, $action = 'message', $max_attempts = 30, $window = 60 ) {
		if ( null === $ip ) {
			$ip = self::get_client_ip();
		}

		$transient_key = 'aria_rate_' . md5( $ip . '_' . $action );
		$attempts = get_transient( $transient_key );

		if ( false === $attempts ) {
			set_transient( $transient_key, 1, $window );
			return true;
		}

		if ( $attempts >= $max_attempts ) {
			return false;
		}

		set_transient( $transient_key, $attempts + 1, $window );
		return true;
	}

	/**
	 * Get client IP address.
	 *
	 * @return string IP address.
	 */
	public static function get_client_ip() {
		$ip_keys = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
		
		foreach ( $ip_keys as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				$ip = $_SERVER[ $key ];
				
				// Handle comma-separated IPs
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = explode( ',', $ip )[0];
				}
				
				$ip = trim( $ip );
				
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
					return $ip;
				}
			}
		}
		
		return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
	}

	/**
	 * Generate secure session ID.
	 *
	 * @return string Session ID.
	 */
	public static function generate_session_id() {
		return wp_generate_password( 32, false, false );
	}

	/**
	 * Validate session ID.
	 *
	 * @param string $session_id Session ID to validate.
	 * @return bool Valid status.
	 */
	public static function validate_session_id( $session_id ) {
		// Must be 32 characters, alphanumeric only
		return preg_match( '/^[a-zA-Z0-9]{32}$/', $session_id ) === 1;
	}

	/**
	 * Check GDPR consent.
	 *
	 * @param array $data Request data.
	 * @return bool Consent status.
	 */
	public static function check_gdpr_consent( $data ) {
		// If GDPR is not enabled, return true
		if ( ! get_option( 'aria_gdpr_enabled', true ) ) {
			return true;
		}

		// Check for explicit consent
		return isset( $data['gdpr_consent'] ) && $data['gdpr_consent'] === 'true';
	}

	/**
	 * Anonymize personal data.
	 *
	 * @param array $data Data containing personal information.
	 * @return array Anonymized data.
	 */
	public static function anonymize_data( $data ) {
		$anonymized = $data;

		// Anonymize email
		if ( isset( $anonymized['guest_email'] ) ) {
			$email_parts = explode( '@', $anonymized['guest_email'] );
			if ( count( $email_parts ) === 2 ) {
				$anonymized['guest_email'] = substr( $email_parts[0], 0, 2 ) . '***@' . $email_parts[1];
			}
		}

		// Anonymize name
		if ( isset( $anonymized['guest_name'] ) ) {
			$name_parts = explode( ' ', $anonymized['guest_name'] );
			$anonymized['guest_name'] = '';
			foreach ( $name_parts as $part ) {
				$anonymized['guest_name'] .= substr( $part, 0, 1 ) . '*** ';
			}
			$anonymized['guest_name'] = trim( $anonymized['guest_name'] );
		}

		// Remove IP if present
		if ( isset( $anonymized['ip_address'] ) ) {
			$anonymized['ip_address'] = 'xxx.xxx.xxx.xxx';
		}

		return $anonymized;
	}

	/**
	 * Log security event.
	 *
	 * @param string $event Event type.
	 * @param array  $data Event data.
	 */
	public static function log_security_event( $event, $data = array() ) {
		$log_entry = array(
			'timestamp' => current_time( 'mysql' ),
			'event'     => $event,
			'ip'        => self::get_client_ip(),
			'user_id'   => get_current_user_id(),
			'data'      => $data,
		);

		// Store in option (limited to last 100 events)
		$security_log = get_option( 'aria_security_log', array() );
		array_unshift( $security_log, $log_entry );
		$security_log = array_slice( $security_log, 0, 100 );
		update_option( 'aria_security_log', $security_log );

		// Also log to error log for critical events
		$critical_events = array( 'api_key_invalid', 'rate_limit_exceeded', 'suspicious_activity' );
		if ( in_array( $event, $critical_events, true ) ) {
			Aria_Logger::debug( 'Security Event', $log_entry );
		}
	}

	/**
	 * Validate file upload.
	 *
	 * @param array $file File data from $_FILES.
	 * @param array $allowed_types Allowed MIME types.
	 * @param int   $max_size Maximum file size in bytes.
	 * @return array|WP_Error Validated file data or error.
	 */
	public static function validate_file_upload( $file, $allowed_types = array(), $max_size = 5242880 ) {
		// Check for upload errors
		if ( $file['error'] !== UPLOAD_ERR_OK ) {
			return new WP_Error( 'upload_error', __( 'File upload failed.', 'aria' ) );
		}

		// Check file size
		if ( $file['size'] > $max_size ) {
			return new WP_Error( 'file_too_large', __( 'File size exceeds maximum allowed.', 'aria' ) );
		}

		// Check MIME type
		$file_type = wp_check_filetype( $file['name'] );
		if ( ! empty( $allowed_types ) && ! in_array( $file_type['type'], $allowed_types, true ) ) {
			return new WP_Error( 'invalid_file_type', __( 'File type not allowed.', 'aria' ) );
		}

		// Additional security checks
		$validate = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
		if ( ! $validate['type'] ) {
			return new WP_Error( 'invalid_file', __( 'File validation failed.', 'aria' ) );
		}

		return $file;
	}

	/**
	 * Generate CSRF token.
	 *
	 * @param string $action Action name.
	 * @return string CSRF token.
	 */
	public static function generate_csrf_token( $action ) {
		return wp_create_nonce( 'aria_' . $action );
	}

	/**
	 * Verify CSRF token.
	 *
	 * @param string $token Token to verify.
	 * @param string $action Action name.
	 * @return bool Valid status.
	 */
	public static function verify_csrf_token( $token, $action ) {
		return wp_verify_nonce( $token, 'aria_' . $action );
	}

	/**
	 * Check if request is from allowed origin.
	 *
	 * @return bool Allowed status.
	 */
	public static function check_request_origin() {
		// Get the request origin
		$origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? $_SERVER['HTTP_ORIGIN'] : '';
		
		if ( empty( $origin ) ) {
			// No origin header, could be same-origin request
			return true;
		}

		// Parse site URL
		$site_url = parse_url( get_site_url() );
		$allowed_origin = $site_url['scheme'] . '://' . $site_url['host'];
		
		if ( isset( $site_url['port'] ) ) {
			$allowed_origin .= ':' . $site_url['port'];
		}

		// Check if origin matches
		return $origin === $allowed_origin;
	}

	/**
	 * Sanitize output for display.
	 *
	 * @param string $content Content to sanitize.
	 * @param string $context Display context.
	 * @return string Sanitized content.
	 */
	public static function sanitize_output( $content, $context = 'html' ) {
		switch ( $context ) {
			case 'html':
				// Allow only safe HTML tags
				$allowed_tags = array(
					'a' => array(
						'href' => array(),
						'title' => array(),
						'target' => array(),
						'rel' => array(),
					),
					'br' => array(),
					'em' => array(),
					'strong' => array(),
					'b' => array(),
					'i' => array(),
					'p' => array(),
					'ul' => array(),
					'ol' => array(),
					'li' => array(),
				);
				return wp_kses( $content, $allowed_tags );

			case 'attribute':
				return esc_attr( $content );

			case 'url':
				return esc_url( $content );

			case 'js':
				return esc_js( $content );

			default:
				return esc_html( $content );
		}
	}
}
